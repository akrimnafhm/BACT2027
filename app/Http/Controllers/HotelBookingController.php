<?php

namespace App\Http\Controllers;

use App\Models\HotelRoom;
use App\Models\HotelReservation;
use App\Services\HotelNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class HotelBookingController extends Controller
{
    public function index(Request $request)
    {
        // 1. Cek status pengguna saat membuka halaman hotel
        if (!Auth::check()) {
            $status = 'guest';
            return view('hotels.index', [
                'hotels'      => HotelRoom::where('is_active', true)->oldest()->get(),
                'status'      => $status,
                'reservation' => null,
            ]);
        }

        $user = Auth::user();

        if (empty($user->nik) || empty($user->phone_number)) {
            return view('hotels.index', [
                'hotels'      => HotelRoom::where('is_active', true)->oldest()->get(),
                'status'      => 'incomplete',
                'reservation' => null,
            ]);
        }

        $existingReservation = HotelReservation::where('user_id', $user->id)
                                ->latest()
                                ->first();

        $existingReservation = $this->syncReservationFromCallback($request, $user, $existingReservation);

        // Jaga konsistensi: batalkan otomatis bila timer pembayaran sudah habis
        // (defense-in-depth, cron rutin menjalankan hal yang sama).
        if ($existingReservation && $existingReservation->status === 'pending' && $this->isReservationExpired($existingReservation)) {
            $this->cancelExpiredReservation($existingReservation);
            $existingReservation = null;
        }

        if ($existingReservation && $existingReservation->status === 'paid') {
            return view('hotels.index', [
                'hotels'      => HotelRoom::where('is_active', true)->oldest()->get(),
                'status'      => 'paid',
                'reservation' => $existingReservation,
            ]);
        }

        if ($existingReservation && $existingReservation->status === 'pending') {
            return view('hotels.index', [
                'hotels'      => HotelRoom::where('is_active', true)->oldest()->get(),
                'status'      => 'pending',
                'reservation' => $existingReservation,
            ]);
        }

        return view('hotels.index', [
            'hotels'      => HotelRoom::where('is_active', true)->oldest()->get(),
            'status'      => 'ready',
            'reservation' => null,
        ]);
    }

    public function create($id)
    {
        $hotel = HotelRoom::where('is_active', true)->findOrFail($id);

        return view('hotels.book', compact('hotel'));
    }

    /**
     * Simpan reservasi kamar hotel peserta lalu lanjut ke pembayaran DOKU.
     */
    public function store(Request $request, $id)
    {
        $user = Auth::user();

        // 0. Pastikan profil lengkap & email terverifikasi sebelum pesan hotel
        if (!$this->isProfileComplete($user)) {
            return redirect()->route('profile.edit')->with('error', 'Silakan lengkapi profil dan verifikasi email/WhatsApp Anda sebelum memesan hotel.');
        }

        // 1. Validasi Tanggal Check-in & Check-out (Kunci 18-21 Jan 2027)
        $request->validate([
            'check_in' => 'required|date|after_or_equal:2027-01-18|before_or_equal:2027-01-20',
            'check_out' => 'required|date|after:check_in|before_or_equal:2027-01-21',
        ], [
            'check_in.after_or_equal' => 'Tanggal Check-in paling cepat adalah 18 Januari 2027.',
            'check_out.before_or_equal' => 'Tanggal Check-out maksimal adalah 21 Januari 2027.',
            'check_out.after' => 'Tanggal Check-out harus setelah tanggal Check-in.'
        ]);

        $room = HotelRoom::where('is_active', true)->findOrFail($id);

        // Auto-cancel reservasi pending yang sudah expired (defense-in-depth)
        $stalePending = HotelReservation::where('user_id', $user->id)
                        ->where('status', 'pending')
                        ->latest()
                        ->first();
        if ($stalePending && $this->isReservationExpired($stalePending)) {
            $this->cancelExpiredReservation($stalePending);
        }

        // 1 user 1 reservasi: blokir jika masih ada reservasi aktif (pending/paid)
        $hasActiveReservation = HotelReservation::where('user_id', $user->id)
                                ->whereIn('status', ['pending', 'paid'])
                                ->exists();
        if ($hasActiveReservation) {
            return redirect()->route('hotels.index')
                ->with('error', 'Anda sudah memiliki reservasi hotel yang aktif. Selesaikan atau batalkan reservasi tersebut terlebih dahulu.');
        }

        // 2. Hitung durasi menginap (malam) & total harga
        $checkInDate = \Carbon\Carbon::parse($request->check_in);
        $checkOutDate = \Carbon\Carbon::parse($request->check_out);
        $nights = $checkInDate->diffInDays($checkOutDate);
        $totalPrice = $room->price_per_night * $nights;

        // 3. Generate kode booking unik (misal: HTL-20270118-AB12)
        $bookingCode = 'HTL-' . $checkInDate->format('Ymd') . '-' . strtoupper(Str::random(4));

        // 3b. Kurangi kuota kamar secara atomik (sama seperti kuota tiket: pending tetap menghabiskan kuota)
        $reserved = HotelRoom::where('id', $room->id)
                    ->where('quota', '>', 0)
                    ->decrement('quota');

        if (!$reserved) {
            return back()->with('error', 'Maaf, kuota kamar hotel ini sudah habis.');
        }

        // 4. Buat reservasi baru (1 user 1 reservasi; pending yang ada sudah ditangani di atas)
        $reservation = HotelReservation::create([
            'user_id' => $user->id,
            'booking_code' => $bookingCode,
            'hotel_room_id' => $room->id,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'total_nights' => $nights,
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
        return !empty($user->email_verified_at) &&
               !empty($user->name) &&
               !empty($user->nik) &&
               !empty($user->gender);
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

        // Kembalikan kuota kamar
        if ($reservation->hotelRoom) {
            $reservation->hotelRoom->increment('quota');
        }

        $reservation->cancelled_at = now();
        $reservation->status = 'cancelled';
        $reservation->save();

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

        // REUSE: Jika link pembayaran DOKU sebelumnya masih valid, jangan buat pembayaran baru.
        if ($reservation->payment_url && $reservation->payment_expired_at && $reservation->payment_expired_at->gt(now())) {
            $paymentUrl = $reservation->payment_url;
            return view('hotels.checkout', compact('reservation', 'room', 'paymentUrl'));
        }

        // Timer sudah habis -> batalkan otomatis, user harus reservasi ulang.
        if ($reservation->payment_expired_at && $reservation->payment_expired_at->lte(now())) {
            $this->cancelExpiredReservation($reservation);
            return redirect()->route('hotels.index')
                ->with('error', 'Timer pembayaran sudah habis, reservasi dibatalkan otomatis. Silakan pesan ulang.');
        }

        // Buat Nomor Invoice unik (Contoh: INV-HTL-1-1785...)
        $invoiceNumber = 'INV-HTL-' . $reservation->id . '-' . time();

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
                'amount' => (int) $reservation->total_price,
                'invoice_number' => $invoiceNumber,
                'currency' => 'IDR',
                'callback_url' => route('hotels.return', [
                    'reservation' => $reservation->id,
                    'invoice_number' => $invoiceNumber,
                    'simulated_paid' => 1,
                ]),
                'notification_url' => config('services.doku.notification_url', url('/api/doku/notification')),
            ],
            'payment' => [
                'payment_due_date' => 1440 // Expired VA/Link dalam menit (24 jam)
            ],
            'customer' => [
                'id' => (string) $user->id,
                'name' => $reservation->guest_name ?: $user->name,
                'email' => $reservation->guest_email ?: $user->email,
                'phone' => $reservation->guest_phone ?: $user->phone_number,
                'country' => 'ID'
            ]
        ];

        $jsonBody = json_encode($requestBody);

        // 2. Buat Tanda Tangan Keamanan (HMAC-SHA256 Signature DOKU)
        $requestId = (string) Str::uuid();
        $requestTimestamp = gmdate("Y-m-d\TH:i:s\Z");
        $requestTarget = "/checkout/v1/payment";

        $digest = base64_encode(hash('sha256', $jsonBody, true));
        $rawSignature = "Client-Id:" . $clientId . "\n"
                      . "Request-Id:" . $requestId . "\n"
                      . "Request-Timestamp:" . $requestTimestamp . "\n"
                      . "Request-Target:" . $requestTarget . "\n"
                      . "Digest:" . $digest;

        $signature = "HMACSHA256=" . base64_encode(hash_hmac('sha256', $rawSignature, $secretKey, true));

        // 3. Tembak API DOKU
        try {
            $response = Http::withHeaders([
                'Client-Id' => $clientId,
                'Request-Id' => $requestId,
                'Request-Timestamp' => $requestTimestamp,
                'Signature' => $signature,
                'Content-Type' => 'application/json',
            ])->send('POST', $baseUrl . $requestTarget, [
                'body' => $jsonBody
            ]);
        } catch (\Throwable $e) {
            Log::error('DOKU hotel checkout request exception', [
                'reservation_id' => $reservation->id,
                'base_url'       => $baseUrl,
                'error'          => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal memproses ke gerbang pembayaran DOKU. Silakan coba lagi.');
        }

        $dokuResult = $response->json();

        // Cek jika berhasil dapat link pembayaran (payment_url) dari DOKU
        if ($response->successful() && isset($dokuResult['response']['payment']['url'])) {
            $paymentUrl = $dokuResult['response']['payment']['url'];
            $paymentExpiredAt = $this->parsePaymentExpiredDate($dokuResult);

            // Simpan invoice_number, link, dan waktu expired link untuk reuse saat "Lanjutkan Pembayaran".
            $reservation->update([
                'invoice_number'      => $invoiceNumber,
                'payment_url'         => $paymentUrl,
                'payment_expired_at'  => $paymentExpiredAt,
            ]);

            return view('hotels.checkout', compact('reservation', 'room', 'paymentUrl'));
        }

        // Jika gagal konek ke DOKU
        Log::error('DOKU hotel checkout failed', [
            'reservation_id' => $reservation->id,
            'base_url'       => $baseUrl,
            'http_status'    => $response->status(),
            'response'       => $dokuResult,
        ]);
        return back()->with('error', 'Gagal memproses ke gerbang pembayaran DOKU. Silakan coba lagi.');
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

        $reservation = $this->syncReservationFromCallback($request, $user, $reservation);

        $isProduction = filter_var(config('services.doku.is_production', false), FILTER_VALIDATE_BOOL);
        $shouldAutoFinalize = !$isProduction && ((int) $request->query('simulated_paid', 0) === 1 || $reservation->status === 'pending');

        if ($shouldAutoFinalize && $reservation->status !== 'paid') {
            $reservation->update(['status' => 'paid']);
            app(HotelNotificationService::class)->sendHotelPaid($reservation);
        }

        return redirect()->route('hotels.index', array_filter([
            'transaction_status' => $request->query('transaction_status'),
            'payment_status' => $request->query('payment_status'),
            'status' => $request->query('status'),
            'invoice_number' => $request->query('invoice_number') ?? $request->query('invoice_no') ?? $request->query('invoice'),
            'order_id' => $request->query('order_id'),
            'simulated_paid' => $request->query('simulated_paid'),
        ], fn ($value) => $value !== null && $value !== ''));
    }

    private function syncReservationFromCallback(Request $request, $user, ?HotelReservation $existingReservation): ?HotelReservation
    {
        $incomingStatus = strtoupper((string) (
            $request->query('transaction_status')
            ?? $request->query('payment_status')
            ?? $request->query('status')
            ?? ''
        ));

        $incomingInvoice = $request->query('invoice_number')
            ?? $request->query('invoice_no')
            ?? $request->query('invoice')
            ?? $request->query('order_id');

        $isPaidCallback = in_array($incomingStatus, ['SUCCESS', 'PAID', 'COMPLETED', 'SETTLED', 'CAPTURED'], true);

        if (!$isPaidCallback && !$incomingInvoice) {
            return $existingReservation;
        }

        $targetReservation = null;

        if ($incomingInvoice) {
            $targetReservation = HotelReservation::where('user_id', $user->id)
                ->where('invoice_number', $incomingInvoice)
                ->first();
        }

        if (!$targetReservation) {
            $targetReservation = $existingReservation;
        }

        if ($targetReservation && $isPaidCallback && $targetReservation->status !== 'paid') {
            $targetReservation->update(['status' => 'paid']);
            app(HotelNotificationService::class)->sendHotelPaid($targetReservation);
        }

        return $targetReservation ?: $existingReservation;
    }

    /**
     * Cek apakah reservasi pending sudah harus dibatalkan otomatis:
     * timer pembayaran (dari DOKU) sudah habis, atau fallback 24 jam sejak dibuat.
     */
    private function isReservationExpired(HotelReservation $reservation): bool
    {
        if ($reservation->payment_expired_at) {
            return $reservation->payment_expired_at->lte(now());
        }

        return $reservation->created_at && $reservation->created_at->lte(now()->subHours(24));
    }

    /**
     * Batalkan reservasi pending secara otomatis dan kembalikan kuota kamar.
     */
    private function cancelExpiredReservation(HotelReservation $reservation): void
    {
        if ($reservation->hotelRoom) {
            $reservation->hotelRoom->increment('quota');
        }

        $reservation->cancelled_at = now();
        $reservation->status = 'cancelled';
        $reservation->save();
    }

    /**
     * Ambil waktu expired link pembayaran dari respons DOKU. Jika tidak tersedia,
     * gunakan fallback 24 jam sejak sekarang.
     */
    private function parsePaymentExpiredDate(array $dokuResult): Carbon
    {
        $raw = $dokuResult['response']['payment']['expired_date']
            ?? $dokuResult['response']['payment']['expiredDate']
            ?? null;

        if ($raw) {
            try {
                // Simpan dalam zona waktu aplikasi agar konsisten dengan created_at
                // (Eloquent menyimpan wall-clock, bukan UTC).
                return Carbon::parse($raw)->setTimezone(config('app.timezone'));
            } catch (\Throwable $e) {
                // ignore, fallback di bawah
            }
        }

        return now()->addHours(24);
    }
}
