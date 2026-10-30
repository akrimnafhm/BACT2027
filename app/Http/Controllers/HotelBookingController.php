<?php

namespace App\Http\Controllers;

use App\Models\HotelReservation;
use App\Models\HotelRoom;
use App\Services\BookingCancellationService;
use App\Services\DokuCancelService;
use App\Services\DokuExpiry;
use App\Services\DokuReconciliationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HotelBookingController extends Controller
{
    /**
     * Halaman "/hotel": daftar reservasi milik peserta (tanpa katalog).
     */
    public function index(Request $request)
    {
        // 1. Cek status pengguna saat membuka halaman hotel
        if (! Auth::check()) {
            return view('hotels.index', [
                'status' => 'guest',
            ]);
        }

        $user = Auth::user();

        if (empty($user->nik) || empty($user->phone_number)) {
            return view('hotels.index', [
                'status' => 'incomplete',
            ]);
        }

        // MULTI-RESERVASI: peserta boleh punya lebih dari satu reservasi aktif
        // (pending + paid), dengan syarat hanya BOLEH ADA SATU pending.
        $reservations = HotelReservation::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'paid'])
            ->latest()
            ->get();

        $latestReservation = $reservations->first();

        $this->syncReservationFromCallback($request, $user, $latestReservation);

        // Jaga konsistensi: rekonsiliasi dengan DOKU bila timer pembayaran sudah habis.
        // Reservasi hanya dibatalkan bila DOKU mengonfirmasi kedaluwarsa; selain itu ditahan.
        $reservations->where('status', 'pending')->each(function ($reservation) {
            if ($this->isReservationExpired($reservation)) {
                app(DokuReconciliationService::class)->reconcileHotel($reservation);
            }
        });

        $reservations = HotelReservation::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'paid'])
            ->with('hotelRoom')
            ->latest()
            ->get();

        return view('hotels.index', [
            'status' => 'ready',
            'reservations' => $reservations,
            'pendingReservation' => $reservations->firstWhere('status', 'pending'),
        ]);
    }

    /**
     * Halaman katalog kamar hotel, dipisah dari daftar reservasi.
     */
    public function catalog()
    {
        if (! Auth::check()) {
            return view('hotels.catalog', [
                'hotels' => HotelRoom::where('is_active', true)->oldest()->get(),
                'status' => 'guest',
            ]);
        }

        $user = Auth::user();

        if (empty($user->nik) || empty($user->phone_number)) {
            return view('hotels.catalog', [
                'hotels' => HotelRoom::where('is_active', true)->oldest()->get(),
                'status' => 'incomplete',
            ]);
        }

        // Rekonsiliasi pending yang kedaluwarsa dengan DOKU agar kuota di katalog akurat.
        // Hanya dibatalkan bila DOKU mengonfirmasi kedaluwarsa.
        HotelReservation::where('user_id', $user->id)
            ->where('status', 'pending')
            ->get()
            ->each(function ($reservation) {
                if ($this->isReservationExpired($reservation)) {
                    app(DokuReconciliationService::class)->reconcileHotel($reservation);
                }
            });

        return view('hotels.catalog', [
            'hotels' => HotelRoom::where('is_active', true)->oldest()->get(),
            'status' => 'ready',
        ]);
    }

    public function create($id)
    {
        $hotel = HotelRoom::where('is_active', true)->findOrFail($id);

        // Sisa kuota kamar untuk membatasi pilihan jumlah kamar di form.
        $remainingQuota = (int) $hotel->quota;

        return view('hotels.book', compact('hotel', 'remainingQuota'));
    }

    /**
     * Simpan reservasi kamar hotel peserta lalu lanjut ke pembayaran DOKU.
     */
    public function store(Request $request, $id)
    {
        $user = Auth::user();

        // 0. Pastikan profil lengkap & email terverifikasi sebelum pesan hotel
        if (! $this->isProfileComplete($user)) {
            return redirect()->route('profile.edit')->with('error', 'Silakan lengkapi profil dan verifikasi email/WhatsApp Anda sebelum memesan hotel.');
        }

        // 1. Validasi Tanggal Check-in & Check-out (Kunci 18-21 Jan 2027) + Jumlah Kamar
        $request->validate([
            'check_in' => 'required|date|after_or_equal:2027-01-18|before_or_equal:2027-01-20',
            'check_out' => 'required|date|after:check_in|before_or_equal:2027-01-21',
            'quantity' => 'required|integer|min:1|max:99',
        ], [
            'check_in.after_or_equal' => 'Tanggal Check-in paling cepat adalah 18 Januari 2027.',
            'check_out.before_or_equal' => 'Tanggal Check-out maksimal adalah 21 Januari 2027.',
            'check_out.after' => 'Tanggal Check-out harus setelah tanggal Check-in.',
            'quantity.required' => 'Jumlah kamar wajib diisi.',
            'quantity.integer' => 'Jumlah kamar harus berupa angka.',
            'quantity.min' => 'Jumlah kamar minimal 1.',
        ]);

        $room = HotelRoom::where('is_active', true)->findOrFail($id);
        $quantity = (int) $request->input('quantity', 1);

        // Rekonsiliasi reservasi pending yang sudah expired (defense-in-depth).
        // Hanya dibatalkan bila DOKU mengonfirmasi kedaluwarsa.
        HotelReservation::where('user_id', $user->id)
            ->where('status', 'pending')
            ->get()
            ->each(function ($reservation) {
                if ($this->isReservationExpired($reservation)) {
                    app(DokuReconciliationService::class)->reconcileHotel($reservation);
                }
            });

        // MULTI-RESERVASI: peserta boleh punya banyak reservasi (termasuk tipe kamar
        // yang sama), dengan syarat tidak ada pembayaran PENDING yang menggantung.
        $hasPendingReservation = HotelReservation::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();
        if ($hasPendingReservation) {
            return redirect()->route('hotels.index')
                ->with('error', 'Anda masih memiliki reservasi hotel yang menunggu pembayaran. Selesaikan atau batalkan terlebih dahulu sebelum memesan kamar lain.');
        }

        if ($quantity > (int) $room->quota) {
            return back()
                ->withInput()
                ->with('error', 'Maaf, sisa kuota kamar ini tinggal '.max(0, (int) $room->quota).'. Silakan sesuaikan jumlah kamar.');
        }

        // 2. Hitung durasi menginap (malam) & total harga (malam × harga/malam × jumlah kamar)
        $checkInDate = Carbon::parse($request->check_in);
        $checkOutDate = Carbon::parse($request->check_out);
        $nights = $checkInDate->diffInDays($checkOutDate);
        $totalPrice = $room->price_per_night * $nights * $quantity;

        // 3. Generate kode booking unik (misal: HTL-20270118-AB12)
        $bookingCode = 'HTL-'.$checkInDate->format('Ymd').'-'.strtoupper(Str::random(4));

        // 3b. Kurangi kuota kamar sebanyak jumlah kamar yang dipesan secara atomik
        // (pending tetap menghabiskan kuota, sama seperti aturan tiket).
        $reserved = HotelRoom::where('id', $room->id)
            ->where('quota', '>=', $quantity)
            ->decrement('quota', $quantity);

        if (! $reserved) {
            return back()->with('error', 'Maaf, kuota kamar hotel ini baru saja habis / tidak cukup untuk '.$quantity.' kamar.');
        }

        // 4. Buat reservasi baru (pending global maksimal 1 sudah divalidasi di atas)
        $reservation = HotelReservation::create([
            'user_id' => $user->id,
            'booking_code' => $bookingCode,
            'hotel_room_id' => $room->id,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'total_nights' => $nights,
            'quantity' => $quantity,
            'total_price' => $totalPrice,
            'guest_name' => $user->name,
            'guest_nik' => $user->nik,
            'guest_phone' => $user->phone_number,
            'guest_email' => $user->email,
        ]);

        // 5. Redirect langsung ke halaman pembayaran DOKU
        return redirect()->route('hotels.checkout', $reservation->id);
    }

    /**
     * Cek apakah profil user sudah lengkap (email terverifikasi, data pribadi terisi).
     */
    private function isProfileComplete($user): bool
    {
        return ! empty($user->email_verified_at) &&
               ! empty($user->name) &&
               ! empty($user->nik) &&
               ! empty($user->gender);
    }

    /**
     * Membatalkan reservasi hotel oleh USER.
     * Hanya berlaku jika status masih 'pending' (belum dibayar).
     * Kuota kamar otomatis dikembalikan sehingga user bisa reservasi ulang.
     */
    public function cancel(Request $request, $id)
    {
        $user = Auth::user();

        $reservation = HotelReservation::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($reservation->status !== 'pending') {
            return back()->with('error', 'Reservasi hanya dapat dibatalkan sebelum pembayaran. Untuk yang sudah lunas, silakan hubungi panitia.');
        }

        // Kembalikan kuota kamar + transisi status aman race (idempoten).
        $cancelled = app(BookingCancellationService::class)->cancelHotel($reservation, 'Dibatalkan oleh user');

        if (! $cancelled) {
            return back()->with('error', 'Reservasi tidak dapat dibatalkan karena statusnya sudah berubah. Silakan muat ulang halaman.');
        }

        // Jejak eksplisit pembatalan manual (satu-satunya penanda di log).
        Log::info('Reservasi hotel dibatalkan manual oleh user', [
            'reservation_id' => $reservation->id,
            'invoice_number' => $reservation->invoice_number,
        ]);

        // Best-effort: batalkan order DOKU yang masih pending agar link tidak bisa dibayar.
        // Kegagalan TIDAK menggagalkan pembatalan lokal (sudah commit) dan tidak mengubah
        // status/kembalikan kuota lagi — hanya dicatat untuk tindak lanjut.
        if ($reservation->invoice_number) {
            $cancelResult = app(DokuCancelService::class)->cancelInvoiceBestEffort(
                $reservation->invoice_number,
                null,
                'Reservasi hotel dibatalkan oleh peserta'
            );

            Log::info('Hotel cancel: hasil DOKU Cancel Order (best-effort)', [
                'reservation_id' => $reservation->id,
                'invoice_number' => $reservation->invoice_number,
                'attempted' => $cancelResult['attempted'],
                'success' => $cancelResult['success'],
                'reason' => $cancelResult['reason'],
                'confirmed' => $cancelResult['confirmed'],
                'http_status' => $cancelResult['http_status'],
            ]);
        }

        return redirect()->route('hotels.index')
            ->with('success', 'Reservasi berhasil dibatalkan. Anda bisa memesan ulang.');
    }

    /**
     * Menyiapkan halaman invoice/checkout dengan DOKU Payment Gateway.
     */
    public function checkout($id)
    {
        $user = Auth::user();

        $reservation = HotelReservation::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($reservation->status === 'paid') {
            return redirect()->route('hotels.index')
                ->with('success', 'Reservasi hotel ini sudah lunas.');
        }

        $room = HotelRoom::find($reservation->hotel_room_id);

        // Lock per reservation_id: cegah dua request paralel membuat 2 payment DOKU.
        $lock = Cache::lock('checkout_hotel_'.$reservation->id, 30);
        if (! $lock->block(5)) {
            return back()->with('error', 'Pembayaran sedang diproses oleh tab/beranda lain. Silakan tunggu beberapa detik.');
        }

        try {
            $reservation->refresh();

            // REUSE: Jika link pembayaran DOKU sebelumnya masih berlaku (expiry resmi
            // DOKU, atau fallback lokal bila DOKU tidak mengirim expiry), pakai ulang.
            if ($reservation->payment_url && app(DokuExpiry::class)->effectiveExpiry($reservation)?->gt(now())) {
                $paymentUrl = $reservation->payment_url;

                return view('hotels.checkout', compact('reservation', 'room', 'paymentUrl'));
            }

            // Timer sudah habis -> rekonsiliasi dengan DOKU. Reservasi hanya dibatalkan
            // bila DOKU mengonfirmasi kedaluwarsa; bila belum final, ditahan.
            if ($this->isReservationExpired($reservation)) {
                app(DokuReconciliationService::class)->reconcileHotel($reservation);
                $reservation->refresh();

                if ($reservation->status === 'paid') {
                    return redirect()->route('hotels.index')
                        ->with('success', 'Reservasi hotel ini sudah lunas.');
                }

                if ($reservation->status !== 'pending') {
                    return redirect()->route('hotels.index')
                        ->with('error', 'Timer pembayaran sudah habis, reservasi dibatalkan otomatis. Silakan pesan ulang.');
                }

                // Sama seperti tiket: HOLD tanpa batas membuat user menunggu
                // tanpa akhir. BUKAN auto-cancel; arahkan ke pembatalan manual.
                if (app(DokuExpiry::class)->heldBeyondGrace($reservation)) {
                    Log::info('DOKU held_beyond_grace', [
                        'reservation_id' => $reservation->id,
                        'invoice_number' => $reservation->invoice_number,
                    ]);

                    return redirect()->route('hotels.index')
                        ->with('error', 'Batas waktu pembayaran sudah lama lewat namun status DOKU belum final (tanpa channel pembayaran yang dipilih). Anda dapat membatalkan reservasi ini lalu memesan ulang, atau menunggu notifikasi DOKU.');
                }

                return redirect()->route('hotels.index')
                    ->with('error', 'Status pembayaran Anda sedang diverifikasi. Silakan muat ulang halaman beberapa saat lagi.');
            }

            // Jika sebelumnya sudah ada invoice_number dari checkout yang belum selesai
            // (timeout/gagal sebelum payment_url tersimpan), jangan buat payment baru
            // tanpa memverifikasi status terlebih dahulu.
            if ($reservation->invoice_number) {
                return back()->with('error', 'Pembayaran sebelumnya sedang diproses atau belum selesai. Jika tidak berhasil dalam beberapa menit, silakan hubungi panitia.');
            }

            // Buat Nomor Invoice unik (Contoh: RC-HTL-1-1785...)
            $invoiceNumber = 'RC-HTL-'.$reservation->id.'-'.time();

            // Simpan invoice_number SEBELUM request ke DOKU agar bisa dilacak
            // meskipun request timeout/gagal sebelum response tersimpan.
            $reservation->update(['invoice_number' => $invoiceNumber]);

            // -------------------------------------------------------------
            // PEMANGGILAN API DOKU CHECKOUT (DIRECT VIA LARAVEL HTTP CLIENT)
            // -------------------------------------------------------------
            $clientId = config('services.doku.client_id');
            $secretKey = config('services.doku.secret_key');
            $isProduction = filter_var(config('services.doku.is_production', false), FILTER_VALIDATE_BOOL);

            $baseUrl = $isProduction
                ? 'https://api.doku.com'
                : 'https://api-sandbox.doku.com';

            // 1. Siapkan Data Pesanan untuk DOKU
            // Line item: SATU BARIS PER KAMAR (qty = jumlah malam per kamar),
            // sehingga total baris = jumlah kamar yang dipesan.
            $unitPrice = (int) ($room->price_per_night ?? round((float) $reservation->total_price / max(1, $reservation->total_nights * $reservation->quantity)));
            $lineItems = [];
            for ($i = 1; $i <= max(1, (int) $reservation->quantity); $i++) {
                $lineItems[] = [
                    'name' => ($room->room_type ?? 'Kamar Hotel').(max(1, (int) $reservation->quantity) > 1 ? ' - Kamar '.$i : ''),
                    'quantity' => $reservation->total_nights ?: 1,
                    'price' => $unitPrice,
                ];
            }

            $requestBody = [
                'order' => [
                    'amount' => (int) $reservation->total_price,
                    'invoice_number' => $invoiceNumber,
                    'currency' => 'IDR',
                    'callback_url' => route('hotels.return', [
                        'reservation' => $reservation->id,
                        'invoice_number' => $invoiceNumber,
                        'simulated_paid' => 1,
                    ]),
                    'notification_url' => config('services.doku.notification_url', url('/api/doku/notification')),
                    'line_items' => $lineItems,
                ],
                'payment' => [
                    'payment_due_date' => app(DokuExpiry::class)->paymentDueDateMinutes(),
                ],
                'customer' => [
                    'id' => (string) $user->id,
                    'name' => $reservation->guest_name ?: $user->name,
                    'email' => $reservation->guest_email ?: $user->email,
                    'phone' => $reservation->guest_phone ?: $user->phone_number,
                    'country' => 'ID',
                ],
            ];

            $jsonBody = json_encode($requestBody);

            // 2. Buat Tanda Tangan Keamanan (HMAC-SHA256 Signature DOKU)
            $requestId = (string) Str::uuid();
            $requestTimestamp = gmdate("Y-m-d\TH:i:s\Z");
            $requestTarget = '/checkout/v1/payment';

            $digest = base64_encode(hash('sha256', $jsonBody, true));
            $rawSignature = 'Client-Id:'.$clientId."\n"
                          .'Request-Id:'.$requestId."\n"
                          .'Request-Timestamp:'.$requestTimestamp."\n"
                          .'Request-Target:'.$requestTarget."\n"
                          .'Digest:'.$digest;

            $signature = 'HMACSHA256='.base64_encode(hash_hmac('sha256', $rawSignature, $secretKey, true));

            Log::info('DOKU hotel checkout initiated', [
                'reservation_id' => $reservation->id,
                'invoice_number' => $invoiceNumber,
                'request_id' => $requestId,
                'amount' => $reservation->total_price,
                'notification_url' => $requestBody['order']['notification_url'],
            ]);

            // 3. Tembak API DOKU
            try {
                $response = Http::withHeaders([
                    'Client-Id' => $clientId,
                    'Request-Id' => $requestId,
                    'Request-Timestamp' => $requestTimestamp,
                    'Signature' => $signature,
                    'Content-Type' => 'application/json',
                ])->send('POST', $baseUrl.$requestTarget, [
                    'body' => $jsonBody,
                ]);
            } catch (\Throwable $e) {
                Log::error('DOKU hotel checkout request exception', [
                    'reservation_id' => $reservation->id,
                    'invoice_number' => $invoiceNumber,
                    'request_id' => $requestId,
                    'base_url' => $baseUrl,
                    'error' => $e->getMessage(),
                ]);

                return back()->with('error', 'Gagal memproses ke gerbang pembayaran DOKU. Silakan coba lagi.');
            }

            $dokuResult = $response->json();

            Log::info('DOKU hotel checkout response', [
                'reservation_id' => $reservation->id,
                'invoice_number' => $invoiceNumber,
                'request_id' => $requestId,
                'http_status' => $response->status(),
                'success' => $response->successful(),
                'payment_due_date_minutes' => app(DokuExpiry::class)->paymentDueDateMinutes(),
                'doku_expired_at' => optional(app(DokuExpiry::class)->parse($dokuResult))->toIso8601String(),
                'payment_method_types' => $dokuResult['response']['payment']['payment_method_types'] ?? null,
            ]);

            // Cek jika berhasil dapat link pembayaran (payment_url) dari DOKU
            if ($response->successful() && isset($dokuResult['response']['payment']['url'])) {
                $paymentUrl = $dokuResult['response']['payment']['url'];
                $paymentExpiredAt = $this->parsePaymentExpiredDate($dokuResult);

                // Simpan invoice_number, link, dan waktu expired link untuk reuse saat "Lanjutkan Pembayaran".
                $reservation->update([
                    'invoice_number' => $invoiceNumber,
                    'payment_url' => $paymentUrl,
                    'payment_expired_at' => $paymentExpiredAt,
                ]);

                return view('hotels.checkout', compact('reservation', 'room', 'paymentUrl'));
            }

            // Jika gagal konek ke DOKU (jangan log body penuh agar tidak membocorkan token).
            Log::error('DOKU hotel checkout failed', [
                'reservation_id' => $reservation->id,
                'invoice_number' => $invoiceNumber,
                'request_id' => $requestId,
                'base_url' => $baseUrl,
                'http_status' => $response->status(),
                'error_code' => $dokuResult['error']['code'] ?? $dokuResult['error_code'] ?? null,
                'error_message' => $dokuResult['error']['message'] ?? $dokuResult['message'] ?? null,
            ]);

            return back()->with('error', 'Gagal memproses ke gerbang pembayaran DOKU. Silakan coba lagi.');
        } finally {
            $lock->release();
        }
    }

    /**
     * Unduh invoice PDF hotel (format ala DOKU). Hanya untuk reservasi yang sudah lunas.
     */
    public function invoice($id)
    {
        $user = Auth::user();

        $reservation = HotelReservation::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        abort_unless($reservation->status === 'paid', 403);

        $pdf = Pdf::loadView('invoices.hotel-pdf', compact('reservation'));

        $filename = 'invoice-'.($reservation->invoice_number ?: $reservation->booking_code).'.pdf';

        if (request()->query('inline')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Menampilkan halaman preview invoice hotel sebelum user mengunduh.
     */
    public function invoicePreview($id)
    {
        $user = Auth::user();

        $reservation = HotelReservation::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        abort_unless($reservation->status === 'paid', 403);

        return view('invoices.preview', [
            'title' => 'Receipt '.($reservation->invoice_number ?: $reservation->booking_code),
            'pdfUrl' => route('hotels.invoice', $reservation->id).'?inline=1',
            'downloadUrl' => route('hotels.invoice', $reservation->id),
            'backUrl' => route('hotels.index'),
        ]);
    }

    /**
     * Menangani user kembali dari DOKU agar status reservasi tersinkron.
     */
    public function paymentReturn(Request $request, HotelReservation $reservation)
    {
        $user = Auth::user();

        if ($reservation->user_id !== $user->id) {
            abort(403);
        }

        // Return URL HANYA untuk UX redirect. Tidak mengubah status.
        // Status hanya diubah oleh webhook DOKU (/api/doku/notification).
        $reservation->refresh();

        return redirect()->route('hotels.index');
    }

    private function syncReservationFromCallback(Request $request, $user, ?HotelReservation $existingReservation): ?HotelReservation
    {
        // Read-only: hanya mencari reservasi berdasarkan invoice_number dari URL.
        // Tidak mengubah status — itu hanya dilakukan oleh webhook DOKU.
        $incomingInvoice = $request->query('invoice_number')
            ?? $request->query('invoice_no')
            ?? $request->query('invoice')
            ?? $request->query('order_id');

        if ($incomingInvoice) {
            $found = HotelReservation::where('user_id', $user->id)
                ->where('invoice_number', $incomingInvoice)
                ->first();

            if ($found) {
                return $found;
            }
        }

        return $existingReservation;
    }

    /**
     * Cek apakah reservasi pending sudah harus dibatalkan otomatis:
     * timer pembayaran (dari DOKU) sudah habis, atau fallback 24 jam sejak dibuat.
     */
    private function isReservationExpired(HotelReservation $reservation): bool
    {
        // Expiry resmi DOKU bila ada, selain itu fallback lokal dari created_at.
        return app(DokuExpiry::class)->isExpired($reservation);
    }

    /**
     * Ambil waktu expired LINK PEMBAYARAN dari respons DOKU.
     *
     * Mengembalikan null bila DOKU tidak menyertakan expiry (jangan mengarang
     * nilai fallback di sini). Fallback lokal ditangani terpisah oleh DokuExpiry.
     */
    private function parsePaymentExpiredDate(array $dokuResult): ?Carbon
    {
        return app(DokuExpiry::class)->parse($dokuResult);
    }
}
