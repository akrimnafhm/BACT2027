<?php

namespace App\Http\Controllers;

use App\Models\HotelRoom;
use App\Models\Ticket;
use App\Models\TicketBooking;
use App\Models\WaGroupLink;
use App\Services\BookingCancellationService;
use App\Services\DokuApiService;
use App\Services\DokuCancelService;
use App\Services\DokuExpiry;
use App\Services\DokuReconciliationService;
use App\Services\DokuStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    /**
     * Menampilkan halaman bridge tiket.
     */
    public function index(Request $request)
    {
        if (! Auth::check()) {
            return view('booking.bridge', [
                'status' => 'guest',
            ]);
        }

        $user = Auth::user();

        $latestBooking = TicketBooking::where('user_id', $user->id)
            ->latest()
            ->first();

        $latestBooking = $this->syncBookingFromCallback($request, $user, $latestBooking);

        if (! $this->isProfileComplete($user)) {
            return view('booking.bridge', [
                'status' => 'incomplete',
                'user' => $user,
                'existingBooking' => $latestBooking,
            ]);
        }

        $bookings = TicketBooking::where('user_id', $user->id)
            ->latest()
            ->get();

        // Semua tiket yang sudah LUNAS — tampilkan semuanya (user boleh punya >1 tiket).
        $paidBookings = $bookings->where('status', 'paid')->values();
        foreach ($paidBookings as $booking) {
            $this->ensureCheckinToken($booking);
        }

        // Pesanan yang masih menunggu pembayaran (paling banyak satu).
        $pendingBooking = $bookings->firstWhere('status', 'pending');
        $paymentDeadlinePassed = false;

        // Jaga konsistensi: rekonsiliasi dengan DOKU bila timer habis / tiket kedaluwarsa.
        // Booking hanya dibatalkan bila DOKU mengonfirmasi kedaluwarsa; selain itu ditahan.
        if ($pendingBooking && $this->isBookingExpired($pendingBooking)) {
            app(DokuReconciliationService::class)->reconcileTicket($pendingBooking);
            $pendingBooking->refresh();

            if ($pendingBooking->status === 'paid') {
                $this->ensureCheckinToken($pendingBooking);
                $paidBookings = $paidBookings->push($pendingBooking);
                $pendingBooking = null;
            } elseif ($pendingBooking->status !== 'pending') {
                $pendingBooking = null;
            } else {
                // Masih pending setelah rekonsiliasi: DOKU belum memberi status final.
                // Jangan tawarkan pembayaran lagi; tampilkan status pemeriksaan.
                $paymentDeadlinePassed = true;
            }
        }

        // Tampilkan e-ticket + (jika ada) kartu lanjut pembayaran.
        if ($paidBookings->isNotEmpty() || $pendingBooking) {
            // Tombol join grup WA ditempel pada TIAP TIKET (bukan blok global).
            // Satu tiket = satu tombol untuk grup dengan kategori yang SAMA persis
            // (Basic -> grup Basic; Basic-Advanced -> grup Basic-Advanced, tanpa
            // ekspansi ke grup komponennya). Tombol hanya muncul bila admin
            // sudah mengisi link grup tersebut.
            foreach ($paidBookings as $booking) {
                $link = WaGroupLink::linkFor($booking->ticket_category);
                $booking->wa_group_url = ($link !== null && trim($link) !== '') ? $link : null;
                $booking->wa_group_label = WaGroupLink::normalizeCategory((string) $booking->ticket_category);
            }

            return view('booking.bridge', [
                'status' => 'tickets',
                'user' => $user,
                'paidBookings' => $paidBookings,
                'pendingBooking' => $pendingBooking,
                'paymentDeadlinePassed' => $paymentDeadlinePassed,
            ]);
        }

        $latestCancelled = $bookings->firstWhere('status', 'cancelled');
        if ($latestCancelled) {
            return view('booking.bridge', [
                'status' => 'cancelled',
                'user' => $user,
                'existingBooking' => $latestCancelled,
            ]);
        }

        $latestDeleted = $bookings->firstWhere('status', 'deleted');
        if ($latestDeleted) {
            return view('booking.bridge', [
                'status' => 'deleted',
                'user' => $user,
                'existingBooking' => $latestDeleted,
            ]);
        }

        return view('booking.bridge', [
            'status' => 'bridge',
            'user' => $user,
            'existingBooking' => null,
        ]);
    }

    /**
     * Membatalkan pemesanan tiket oleh USER.
     * Hanya berlaku jika status masih 'pending' (belum dibayar).
     * Kuota tiket otomatis dikembalikan.
     */
    public function cancel(Request $request, $id)
    {
        $user = Auth::user();

        $booking = TicketBooking::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Peserta manual dikelola panitia — pembatalan lewat panitia, bukan user.
        if ($booking->source === 'manual') {
            return back()->with('error', 'Peserta manual dikelola oleh panitia. Silakan hubungi panitia untuk pembatalan.');
        }

        if ($booking->status !== 'pending') {
            return back()->with('error', 'Pemesanan hanya dapat dibatalkan sebelum pembayaran. Untuk tiket yang sudah lunas, silakan hubungi panitia untuk proses refund.');
        }

        // Kembalikan kuota tiket + transisi status aman race (idempoten).
        $cancelled = app(BookingCancellationService::class)->cancelTicket($booking, 'Dibatalkan oleh user');

        if (! $cancelled) {
            return back()->with('error', 'Pemesanan tidak dapat dibatalkan karena statusnya sudah berubah. Silakan muat ulang halaman.');
        }

        // Jejak eksplisit: pembatalan manual tidak melewati rekonsiliasi DOKU,
        // jadi baris ini satu-satunya penanda di log (tanpa kredensial).
        Log::info('Booking tiket dibatalkan manual oleh user', [
            'booking_id' => $booking->id,
            'invoice_number' => $booking->invoice_number,
        ]);

        // Best-effort: batalkan order DOKU yang masih pending agar link tidak bisa dibayar.
        // Tidak mensyaratkan channel sudah dipilih; DOKU yang menentukan apakah order masih
        // dapat dibatalkan. Kegagalan remote tidak menggagalkan pembatalan lokal (sudah commit).
        if ($booking->invoice_number) {
            $cancelResult = app(DokuCancelService::class)->cancelInvoiceBestEffort(
                $booking->invoice_number,
                null,
                'Booking tiket dibatalkan oleh peserta'
            );

            Log::info('Ticket cancel: hasil DOKU Cancel Order (best-effort)', [
                'booking_id' => $booking->id,
                'invoice_number' => $booking->invoice_number,
                'attempted' => $cancelResult['attempted'],
                'success' => $cancelResult['success'],
                'reason' => $cancelResult['reason'],
                'confirmed' => $cancelResult['confirmed'],
                'http_status' => $cancelResult['http_status'],
            ]);
        }

        return back()->with('success', 'Pemesanan berhasil dibatalkan. Kuota tiket telah dikembalikan.');
    }

    /**
     * Menampilkan halaman form booking tiket.
     */
    public function form()
    {
        if (! Auth::check()) {
            return redirect()->route('booking.index');
        }

        $user = Auth::user();

        if (! $this->isProfileComplete($user)) {
            return redirect()->route('booking.index')
                ->with('error', 'Mohon lengkapi profil terlebih dahulu sebelum melanjutkan ke halaman booking.');
        }

        $existingBooking = TicketBooking::where('user_id', $user->id)
            ->latest()
            ->first();

        // Beli tiket berikutnya hanya diperbolehkan jika tidak ada pesanan yang BELUM LUNAS (pending).
        $hasPendingBooking = TicketBooking::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        // Jika pending sudah kedaluwarsa, rekonsiliasi dengan DOKU agar user bisa
        // langsung booking ulang. Bila status DOKU belum final, booking tetap ditahan.
        if ($hasPendingBooking) {
            $stalePending = TicketBooking::where('user_id', $user->id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if ($stalePending && $this->isBookingExpired($stalePending)) {
                app(DokuReconciliationService::class)->reconcileTicket($stalePending);
            } elseif ($stalePending) {
                $resolution = $this->resolveUnpayablePendingBooking($stalePending);

                if ($resolution === 'gateway_error') {
                    return redirect()->route('booking.index')
                        ->with('error', 'Gagal memproses ke gerbang pembayaran DOKU. Silakan coba lagi beberapa saat lagi.');
                }
            }

            $hasPendingBooking = TicketBooking::where('user_id', $user->id)
                ->where('status', 'pending')
                ->exists();
        }

        if ($hasPendingBooking) {
            return redirect()->route('booking.index')
                ->with('error', 'Anda masih memiliki pesanan tiket yang belum lunas. Silakan selesaikan atau batalkan pembayaran tersebut terlebih dahulu sebelum membeli tiket lain.');
        }

        // Kategori tiket yang sudah dimiliki (tidak dihitung jika sudah dibatalkan).
        // Aturan: 1 kategori hanya boleh dimiliki 1 tiket.
        $ownedCategories = TicketBooking::where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->pluck('ticket_category')
            ->all();

        $hotels = HotelRoom::where('is_active', true)->get();
        $now = Carbon::now();

        $activeTickets = Ticket::where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            })
            ->get();

        foreach ($activeTickets as $ticket) {
            $ticket->display_name = $ticket->ticket_name.' - '.$ticket->ticket_category;
        }

        return view('booking.index', [
            'status' => 'ready',
            'user' => $user,
            'tickets' => $activeTickets,
            'existingBooking' => $existingBooking ?? null,
            'ownedCategories' => $ownedCategories,
            'hotels' => $hotels,
        ]);
    }

    /**
     * Memproses pesanan tiket dan MENGUNCI (lock) harga, jenis tiket, serta data peserta.
     */
    public function process(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'profession' => 'required|string|max:255',
            'plataran_sehat_email' => 'required|email|max:255',
            'institution_name' => 'required|string|max:255',
            'institution_city' => 'required|string|max:255',
            'institution_province' => 'required|string|max:255',
            'gelar_depan' => 'nullable|string|max:100',
            'gelar_belakang' => 'nullable|string|max:150',
        ]);

        $user = Auth::user();
        $ticket = Ticket::findOrFail($request->ticket_id);
        $now = Carbon::now();

        // Identitas peserta SELALU diambil dari profil akun, bukan dari input form,
        // agar satu akun tidak bisa dipakai membeli tiket untuk orang lain.
        $gelarDepan = trim((string) $request->input('gelar_depan'));
        $gelarBelakang = trim((string) $request->input('gelar_belakang'));
        $nameWithTitle = $user->name;

        if ($gelarDepan !== '') {
            $nameWithTitle = rtrim($gelarDepan, '.').'. '.$nameWithTitle;
        }
        if ($gelarBelakang !== '') {
            $nameWithTitle .= ', '.$gelarBelakang;
        }

        if (! $this->isProfileComplete($user)) {
            return back()->with('error', 'Mohon lengkapi profil terlebih dahulu sebelum memesan tiket.');
        }

        // Validasi keamanan tiket
        if (! $ticket->is_active) {
            return back()->with('error', 'Maaf, tiket ini sedang dinonaktifkan oleh panitia.');
        }
        if ($ticket->start_date && $ticket->start_date->gt($now)) {
            return back()->with('error', 'Maaf, masa penjualan tiket promo ini belum dimulai.');
        }
        if ($ticket->end_date && $ticket->end_date->lt($now)) {
            return back()->with('error', 'Maaf, masa berlaku tiket promo ini sudah berakhir.');
        }

        // ATURAN MULTI-TIKET:
        // 1) Beli tiket berikutnya hanya jika tidak ada pesanan PENDING (wajib lunas/batalkan dulu).
        $pendingBooking = TicketBooking::where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($pendingBooking) {
            if ($this->isBookingExpired($pendingBooking)) {
                app(DokuReconciliationService::class)->reconcileTicket($pendingBooking);
            } else {
                $resolution = $this->resolveUnpayablePendingBooking($pendingBooking);

                if ($resolution === 'gateway_error') {
                    return back()->with('error', 'Gagal memproses ke gerbang pembayaran DOKU. Silakan coba lagi beberapa saat lagi.');
                }
            }

            $hasPendingBooking = TicketBooking::where('user_id', $user->id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingBooking) {
                return back()->with('error', 'Anda masih memiliki pesanan tiket yang belum lunas. Silakan selesaikan atau batalkan pembayaran tersebut terlebih dahulu sebelum membeli tiket lain.');
            }
        }

        // 2) Satu kategori hanya boleh dimiliki satu tiket (tiket yang dibatalkan tidak dihitung).
        $ownedCategories = TicketBooking::where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->pluck('ticket_category')
            ->all();
        if (in_array($ticket->ticket_category, $ownedCategories)) {
            return back()->with('error', 'Anda sudah memiliki tiket kategori "'.$ticket->ticket_category.'". Setiap kategori hanya dapat dibeli satu tiket.');
        }

        // LOCK KUOTA + LOCK DATA DALAM SATU TRANSAKSI ATOMIK.
        // Pengurangan kuota dilakukan bersyarat (WHERE quota > 0) agar aman dari race
        // condition saat kuota menipis & banyak user memesan bersamaan. Jika gagal,
        // seluruh transaksi di-rollback sehingga kuota tidak berkurang tanpa booking.
        try {
            $booking = DB::transaction(function () use ($ticket, $request, $user, $nameWithTitle) {
                $reserved = Ticket::where('id', $ticket->id)
                    ->where('quota', '>', 0)
                    ->decrement('quota');

                if ($reserved === 0) {
                    throw new \RuntimeException('kuota_habis');
                }

                // LOCK DATA: Menyimpan snapshot harga, jenis tiket, dan identitas peserta
                return TicketBooking::updateOrCreate(
                    ['user_id' => $user->id, 'status' => 'pending'],
                    [
                        'ticket_id' => $ticket->id,
                        'ticket_name' => $ticket->ticket_name,       // Lock Nama Tiket
                        'ticket_category' => $ticket->ticket_category,   // Lock Kategori
                        'amount' => $ticket->price,             // Lock Harga Beli
                        'full_name' => $user->name,
                        'name_with_title' => $nameWithTitle,
                        'nik' => $user->nik,
                        'profession' => $request->profession,
                        'whatsapp_number' => $user->phone_number,
                        'gmail_account' => $user->email,
                        'plataran_sehat_email' => $request->plataran_sehat_email,
                        'institution_name' => $request->institution_name,
                        'institution_district' => $request->institution_district ?? null,
                        'institution_city' => $request->institution_city,
                        'institution_province' => $request->institution_province,
                    ]
                );
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'kuota_habis') {
                return back()->with('error', 'Maaf, kuota tiket ini baru saja habis.');
            }
            throw $e;
        }

        return redirect()->route('checkout', $booking->id);
    }

    /**
     * Menyiapkan halaman checkout dengan DOKU Payment Gateway
     */
    public function checkout($id)
    {
        $user = Auth::user();

        $booking = TicketBooking::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($booking->status === 'paid') {
            return redirect()->route('booking.index')
                ->with('success', 'Tiket ini sudah lunas.');
        }

        if ($booking->status !== 'pending') {
            return redirect()->route('booking.index')
                ->with('error', 'Pemesanan ini sudah tidak aktif. Silakan lakukan pemesanan ulang.');
        }

        // Peserta manual tidak membayar online — dikonfirmasi langsung oleh panitia.
        if ($booking->source === 'manual') {
            return redirect()->route('booking.index')
                ->with('error', 'Tiket peserta manual dikonfirmasi oleh panitia, tidak perlu melakukan pembayaran online.');
        }

        // Rekonsiliasi dengan DOKU bila timer habis / tiket tidak berlaku.
        // Booking hanya dibatalkan bila DOKU mengonfirmasi kedaluwarsa.
        if ($this->isBookingExpired($booking)) {
            app(DokuReconciliationService::class)->reconcileTicket($booking);
            $booking->refresh();

            if ($booking->status === 'paid') {
                return redirect()->route('booking.index')
                    ->with('success', 'Tiket ini sudah lunas.');
            }

            if ($booking->status !== 'pending') {
                return redirect()->route('booking.index')
                    ->with('error', 'Pemesanan otomatis dibatalkan karena melewati batas waktu pembayaran atau tiket sudah tidak berlaku. Silakan lakukan pemesanan ulang.');
            }

            // HOLD tanpa batas waktu membuat user menunggu tanpa akhir (kasus
            // invoice 141: PENDING + ORDER_GENERATED tanpa channel). Bila masa
            // tenggang lewat, beri tahu user secara eksplisit bahwa ia bisa
            // membatalkan lalu memesan ulang. BUKAN auto-cancel: status lokal
            // hanya berubah lewat DokuStatus atau aksi eksplisit user.
            if (app(DokuExpiry::class)->heldBeyondGrace($booking)) {
                Log::info('DOKU held_beyond_grace', [
                    'booking_id' => $booking->id,
                    'invoice_number' => $booking->invoice_number,
                ]);

                return redirect()->route('booking.index')
                    ->with('error', 'Batas waktu pembayaran sudah lama lewat namun status DOKU belum final (tanpa channel pembayaran yang dipilih). Anda dapat membatalkan pesanan ini lalu membuat pesanan baru, atau menunggu notifikasi DOKU.');
            }

            return redirect()->route('booking.index')
                ->with('error', 'Waktu pembayaran telah berakhir. Kami sedang memeriksa status pembayaran ke DOKU. Silakan perbarui status beberapa saat lagi.');
        }

        $ticket = Ticket::find($booking->ticket_id);
        $displayName = $booking->ticket_name.' - '.$booking->ticket_category;

        // Lock per booking_id: cegah dua request paralel membuat 2 payment DOKU.
        $lock = Cache::lock('checkout_ticket_'.$booking->id, 30);
        if (! $lock->block(5)) {
            return back()->with('error', 'Pembayaran sedang diproses oleh tab/beranda lain. Silakan tunggu beberapa detik.');
        }

        try {
            // Reload untuk mendapatkan state terbaru di bawah lock.
            $booking->refresh();

            // REUSE: Jika link pembayaran DOKU sebelumnya masih berlaku (expiry resmi
            // DOKU, atau fallback lokal bila DOKU tidak mengirim expiry), pakai ulang.
            if ($booking->payment_url && app(DokuExpiry::class)->effectiveExpiry($booking)?->gt(now())) {
                return view('booking.checkout', compact('booking', 'ticket', 'displayName') + ['paymentUrl' => $booking->payment_url]);
            }

            // Jika sebelumnya sudah ada invoice_number dari checkout yang belum selesai
            // (timeout/gagal sebelum payment_url tersimpan), verifikasi dulu ke DOKU
            // agar user tidak tertahan saat DOKU sedang bermasalah (mis. 503).
            if ($booking->invoice_number) {
                $previous = app(DokuApiService::class)->checkStatus($booking->invoice_number);
                $httpStatus = $previous['http_status'] ?? null;

                if ($httpStatus === 404) {
                    // DOKU tidak mengenal invoice ini: order tidak pernah terbentuk,
                    // aman membuat invoice baru saat user mencoba lagi.
                    $booking->update([
                        'invoice_number' => null,
                        'payment_url' => null,
                        'payment_expired_at' => null,
                    ]);
                } elseif (in_array($previous['outcome'] ?? null, [DokuStatus::PAID, DokuStatus::EXPIRED], true)) {
                    app(DokuReconciliationService::class)->reconcileTicket($booking);
                    $booking->refresh();

                    return redirect()->route('booking.index')->with(
                        $booking->status === 'paid' ? 'success' : 'error',
                        $booking->status === 'paid'
                            ? 'Tiket ini sudah lunas.'
                            : 'Pemesanan sebelumnya sudah kedaluwarsa. Silakan lakukan pemesanan ulang.'
                    );
                } elseif ($httpStatus === 200) {
                    return back()->with('error', 'Pembayaran sebelumnya sedang diproses atau belum selesai. Jika tidak berhasil dalam beberapa menit, silakan hubungi panitia.');
                } else {
                    return back()->with('error', 'Gagal memproses ke gerbang pembayaran DOKU. Silakan coba lagi beberapa saat lagi.');
                }
            }

            // Buat Nomor Invoice unik (Contoh: RC-BACT-1-1785...)
            $invoiceNumber = 'RC-BACT-'.$booking->id.'-'.time();

            // Simpan invoice_number SEBELUM request ke DOKU agar bisa dilacak
            // meskipun request timeout/gagal sebelum response tersimpan.
            $booking->update(['invoice_number' => $invoiceNumber]);

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
            $requestBody = [
                'order' => [
                    'amount' => $booking->amount,
                    'invoice_number' => $invoiceNumber,
                    'currency' => 'IDR',
                    'callback_url' => route('booking.return', [
                        'booking' => $booking->id,
                        'invoice_number' => $invoiceNumber,
                        'simulated_paid' => 1,
                    ]),
                    'notification_url' => config('services.doku.notification_url', url('/api/doku/notification')),
                    'line_items' => [
                        [
                            'name' => ($booking->ticket_name ? $booking->ticket_name.' - ' : '').$booking->ticket_category,
                            'quantity' => 1,
                            'price' => (int) $booking->amount,
                        ],
                    ],
                ],
                'payment' => [
                    'payment_due_date' => app(DokuExpiry::class)->paymentDueDateMinutes(),
                ],
                'customer' => [
                    'id' => (string) $user->id,
                    'name' => $booking->full_name,
                    'email' => $booking->gmail_account,
                    'phone' => $booking->whatsapp_number,
                    'address' => $booking->institution_name.', '.$booking->institution_city,
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

            Log::info('DOKU checkout initiated', [
                'booking_id' => $booking->id,
                'invoice_number' => $invoiceNumber,
                'request_id' => $requestId,
                'amount' => $booking->amount,
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
                Log::error('DOKU checkout request exception', [
                    'booking_id' => $booking->id,
                    'invoice_number' => $invoiceNumber,
                    'request_id' => $requestId,
                    'base_url' => $baseUrl,
                    'error' => $e->getMessage(),
                ]);

                return back()->with('error', 'Gagal memproses ke gerbang pembayaran DOKU. Silakan coba lagi.');
            }

            $dokuResult = $response->json() ?? [];

            Log::info('DOKU checkout response', [
                'booking_id' => $booking->id,
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

                // Simpan invoice_number, link, dan waktu expired link agar bisa di-reuse saat "Lanjutkan Pembayaran".
                $paymentExpiredAt = $this->parsePaymentExpiredDate($dokuResult);
                $booking->update([
                    'invoice_number' => $invoiceNumber,
                    'payment_url' => $paymentUrl,
                    'payment_expired_at' => $paymentExpiredAt,
                ]);

                return view('booking.checkout', compact('booking', 'ticket', 'displayName', 'paymentUrl'));
            }

            // Jika gagal konek ke DOKU (jangan log body penuh agar tidak membocorkan token).
            Log::error('DOKU checkout failed', [
                'booking_id' => $booking->id,
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
     * Unduh invoice PDF tiket (format ala DOKU). Hanya untuk tiket yang sudah lunas.
     */
    public function invoice($id)
    {
        $user = Auth::user();

        $booking = TicketBooking::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        abort_unless($booking->status === 'paid', 403);

        $pdf = Pdf::loadView('invoices.ticket-pdf', compact('booking'));

        $filename = 'invoice-'.($booking->invoice_number ?: ('ticket-'.$booking->id)).'.pdf';

        if (request()->query('inline')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Menampilkan halaman preview invoice sebelum user mengunduh.
     */
    public function invoicePreview($id)
    {
        $user = Auth::user();

        $booking = TicketBooking::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        abort_unless($booking->status === 'paid', 403);

        return view('invoices.preview', [
            'title' => 'Receipt '.($booking->invoice_number ?: ('Tiket-'.$booking->id)),
            'pdfUrl' => route('invoice.ticket', $booking->id).'?inline=1',
            'downloadUrl' => route('invoice.ticket', $booking->id),
            'backUrl' => route('booking.index'),
        ]);
    }

    /**
     * Menangani user kembali dari DOKU agar status booking tersinkron sebelum masuk bridge.
     */
    public function paymentReturn(Request $request, TicketBooking $booking)
    {
        $user = Auth::user();

        if ($booking->user_id !== $user->id) {
            abort(403);
        }

        // Return URL HANYA untuk UX redirect. Tidak mengubah status.
        // Status hanya diubah oleh webhook DOKU (/api/doku/notification).
        $booking->refresh();

        return redirect()->route('booking.index');
    }

    private function isProfileComplete($user): bool
    {
        return ! empty($user->email_verified_at) &&
               ! empty($user->name) &&
               ! empty($user->nik) &&
               ! empty($user->gender);
    }

    /**
     * Tangani pesanan pending yang tidak punya link pembayaran, biasanya karena
     * checkout gagal sebelum DOKU membuat order (mis. DOKU 503/maintenance).
     *
     * Tanpa ini, pesanan "hantu" menahan pembelian berikutnya sampai fallback
     * lokal 24 jam habis.
     *
     * @return string 'blocked' (masih ada link yang bisa dibayar),
     *                'resolved' (sudah lunas/dibatalkan sehingga tidak menahan),
     *                atau 'gateway_error' (DOKU tidak bisa dipastikan).
     */
    private function resolveUnpayablePendingBooking(TicketBooking $pending): string
    {
        if ($pending->payment_url) {
            return 'blocked';
        }

        // Belum pernah checkout: user masih punya pesanan yang harus diselesaikan
        // atau dibatalkan manual, jadi tetap ditahan.
        if (blank($pending->invoice_number)) {
            return 'blocked';
        }

        $result = app(DokuApiService::class)->checkStatus($pending->invoice_number);
        $httpStatus = $result['http_status'] ?? null;
        $outcome = $result['outcome'] ?? DokuStatus::HOLD;

        if ($outcome === DokuStatus::PAID) {
            app(DokuReconciliationService::class)->reconcileTicket($pending);

            return 'resolved';
        }

        // DOKU tidak mengenal invoice ini: order tidak pernah terbentuk, aman
        // dibatalkan lokal dan kuota dikembalikan.
        if ($outcome === DokuStatus::EXPIRED || $httpStatus === 404) {
            app(BookingCancellationService::class)->cancelTicket(
                $pending,
                'Dibatalkan otomatis: checkout DOKU tidak menghasilkan link pembayaran'
            );

            return 'resolved';
        }

        // DOKU menjawab tetapi order belum final: jangan buat pesanan ganda.
        if ($httpStatus === 200) {
            return 'blocked';
        }

        // DOKU tidak dapat dihubungi (mis. 503 maintenance).
        return 'gateway_error';
    }

    /**
     * Cek apakah booking pending sudah harus dibatalkan otomatis:
     * timer pembayaran dari DOKU (payment_expired_at) sudah habis,
     * atau fallback 24 jam sejak dibuat, atau tiket sudah tidak berlaku.
     */
    private function isBookingExpired(TicketBooking $booking): bool
    {
        // 1. Expiry RESMI dari DOKU (bila ada) — jangan campur dengan fallback.
        if ($booking->payment_expired_at) {
            return $booking->payment_expired_at->lte(now());
        }

        // 2. Fallback lokal eksplisit (created_at + jam konfigurasi).
        if (app(DokuExpiry::class)->isExpired($booking)) {
            return true;
        }

        // 3. Tiket sudah tidak berlaku (hanya relevan bila tidak ada timer pembayaran).
        $ticket = $booking->ticket;
        if ($ticket && $ticket->end_date && $ticket->end_date->lt(now())) {
            return true;
        }

        return false;
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

    /**
     * Pastikan booking punya checkin_token unik (dipakai di QR check-in).
     */
    private function ensureCheckinToken(TicketBooking $booking): TicketBooking
    {
        if (empty($booking->checkin_token)) {
            $booking->update([
                'checkin_token' => 'BACT-'.$booking->id.'-'.strtoupper(Str::random(10)),
            ]);
            $booking->refresh();
        }

        return $booking;
    }

    private function syncBookingFromCallback(Request $request, $user, ?TicketBooking $existingBooking): ?TicketBooking
    {
        // Read-only: hanya mencari booking berdasarkan invoice_number dari URL.
        // Tidak mengubah status — itu hanya dilakukan oleh webhook DOKU.
        $incomingInvoice = $request->query('invoice_number')
            ?? $request->query('invoice_no')
            ?? $request->query('invoice')
            ?? $request->query('order_id');

        if ($incomingInvoice) {
            $found = TicketBooking::where('user_id', $user->id)
                ->where('invoice_number', $incomingInvoice)
                ->first();

            if ($found) {
                return $found;
            }
        }

        return $existingBooking;
    }
}
