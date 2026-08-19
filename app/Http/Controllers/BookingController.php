<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\HotelRoom;
use App\Models\TicketBooking;
use App\Services\TicketNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        if (!Auth::check()) {
            return view('booking.bridge', [
                'status'  => 'guest',
            ]);
        }

        $user = Auth::user();

        $latestBooking = TicketBooking::where('user_id', $user->id)
                            ->latest()
                            ->first();

        $latestBooking = $this->syncBookingFromCallback($request, $user, $latestBooking);

        if (!$this->isProfileComplete($user)) {
            return view('booking.bridge', [
                'status'          => 'incomplete',
                'user'            => $user,
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

        // Jaga konsistensi: batalkan otomatis bila sudah melewati 24 jam / tiket kedaluwarsa
        // (defense-in-depth, cron rutin menjalankan hal yang sama).
        if ($pendingBooking && $this->isBookingExpired($pendingBooking)) {
            $this->cancelExpiredBooking($pendingBooking);
            $pendingBooking = null;
        }

        // Tampilkan e-ticket + (jika ada) kartu lanjut pembayaran.
        if ($paidBookings->isNotEmpty() || $pendingBooking) {
            return view('booking.bridge', [
                'status'         => 'tickets',
                'user'           => $user,
                'paidBookings'   => $paidBookings,
                'pendingBooking' => $pendingBooking,
            ]);
        }

        $latestCancelled = $bookings->firstWhere('status', 'cancelled');
        if ($latestCancelled) {
            return view('booking.bridge', [
                'status'          => 'cancelled',
                'user'            => $user,
                'existingBooking' => $latestCancelled,
            ]);
        }

        $latestDeleted = $bookings->firstWhere('status', 'deleted');
        if ($latestDeleted) {
            return view('booking.bridge', [
                'status'          => 'deleted',
                'user'            => $user,
                'existingBooking' => $latestDeleted,
            ]);
        }

        return view('booking.bridge', [
            'status'          => 'bridge',
            'user'            => $user,
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

        // Kembalikan kuota tiket
        if ($booking->ticket_id) {
            \App\Models\Ticket::where('id', $booking->ticket_id)->increment('quota');
        }

        $noteLine = now()->format('d M Y H:i') . ' — Dibatalkan oleh user';
        $booking->notes = trim(($booking->notes ? $booking->notes . "\n" : '') . $noteLine);
        $booking->notes_updated_at = now();
        $booking->cancelled_at = now();
        $booking->status = 'cancelled';
        $booking->save();

        return back()->with('success', 'Pemesanan berhasil dibatalkan. Kuota tiket telah dikembalikan.');
    }

    /**
     * Menampilkan halaman form booking tiket.
     */
    public function form()
    {
        if (!Auth::check()) {
            return redirect()->route('booking.index');
        }

        $user = Auth::user();

        if (!$this->isProfileComplete($user)) {
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

        // Jika pending sudah kedaluwarsa, batalkan otomatis agar user bisa langsung booking ulang.
        if ($hasPendingBooking) {
            $stalePending = TicketBooking::where('user_id', $user->id)
                            ->where('status', 'pending')
                            ->latest()
                            ->first();
            if ($stalePending && $this->isBookingExpired($stalePending)) {
                $this->cancelExpiredBooking($stalePending);
                $hasPendingBooking = false;
            }
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
            $ticket->display_name = $ticket->ticket_name . ' - ' . $ticket->ticket_category;
        }

        return view('booking.index', [
            'status'          => 'ready',
            'user'            => $user,
            'tickets'         => $activeTickets,
            'existingBooking' => $existingBooking ?? null,
            'ownedCategories' => $ownedCategories,
            'hotels'          => $hotels,
        ]);
    }

    /**
     * Memproses pesanan tiket dan MENGUNCI (lock) harga, jenis tiket, serta data peserta.
     */
    public function process(Request $request)
    {
        $request->validate([
            'ticket_id'            => 'required|exists:tickets,id',
            'full_name'            => 'required|string|max:255',
            'name_with_title'      => 'required|string|max:255',
            'nik'                  => 'required|digits:16',
            'profession'           => 'required|string|max:255',
            'whatsapp_number'      => 'required|string|max:20',
            'gmail_account'        => 'required|email|max:255',
            'plataran_sehat_email' => 'required|email|max:255',
            'institution_name'     => 'required|string|max:255',
            'institution_district' => 'required|string|max:255',
            'institution_city'     => 'required|string|max:255',
            'institution_province' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $ticket = Ticket::findOrFail($request->ticket_id);
        $now = Carbon::now();

        // Validasi keamanan tiket
        if (!$ticket->is_active) {
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
        $hasPendingBooking = TicketBooking::where('user_id', $user->id)
                            ->where('status', 'pending')
                            ->exists();
        if ($hasPendingBooking) {
            return back()->with('error', 'Anda masih memiliki pesanan tiket yang belum lunas. Silakan selesaikan atau batalkan pembayaran tersebut terlebih dahulu sebelum membeli tiket lain.');
        }

        // 2) Satu kategori hanya boleh dimiliki satu tiket (tiket yang dibatalkan tidak dihitung).
        $ownedCategories = TicketBooking::where('user_id', $user->id)
                            ->where('status', '!=', 'cancelled')
                            ->pluck('ticket_category')
                            ->all();
        if (in_array($ticket->ticket_category, $ownedCategories)) {
            return back()->with('error', 'Anda sudah memiliki tiket kategori "' . $ticket->ticket_category . '". Setiap kategori hanya dapat dibeli satu tiket.');
        }

        // LOCK KUOTA + LOCK DATA DALAM SATU TRANSAKSI ATOMIK.
        // Pengurangan kuota dilakukan bersyarat (WHERE quota > 0) agar aman dari race
        // condition saat kuota menipis & banyak user memesan bersamaan. Jika gagal,
        // seluruh transaksi di-rollback sehingga kuota tidak berkurang tanpa booking.
        try {
            $booking = \Illuminate\Support\Facades\DB::transaction(function () use ($ticket, $request, $user) {
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
                        'ticket_id'            => $ticket->id,
                        'ticket_name'          => $ticket->ticket_name,       // Lock Nama Tiket
                        'ticket_category'      => $ticket->ticket_category,   // Lock Kategori
                        'amount'               => $ticket->price,             // Lock Harga Beli
                        'full_name'            => $request->full_name,
                        'name_with_title'      => $request->name_with_title,
                        'nik'                  => $request->nik,
                        'profession'           => $request->profession,
                        'whatsapp_number'      => $request->whatsapp_number,
                        'gmail_account'        => $request->gmail_account,
                        'plataran_sehat_email' => $request->plataran_sehat_email,
                        'institution_name'     => $request->institution_name,
                        'institution_district' => $request->institution_district,
                        'institution_city'     => $request->institution_city,
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

        // Batalkan otomatis bila melewati batas waktu pembayaran (24 jam) atau tiket sudah tidak berlaku.
        if ($this->isBookingExpired($booking)) {
            $this->cancelExpiredBooking($booking);
            return redirect()->route('booking.index')
                             ->with('error', 'Pemesanan otomatis dibatalkan karena melewati batas waktu pembayaran (24 jam) atau tiket sudah tidak berlaku. Silakan lakukan pemesanan ulang.');
        }

        // REUSE: Jika link pembayaran DOKU sebelumnya masih valid, jangan buat pembayaran baru.
        if ($booking->payment_url && $booking->payment_expired_at && $booking->payment_expired_at->gt(now())) {
            $ticket = Ticket::find($booking->ticket_id);
            $displayName = $booking->ticket_name . ' - ' . $booking->ticket_category;
            return view('booking.checkout', compact('booking', 'ticket', 'displayName') + ['paymentUrl' => $booking->payment_url]);
        }

        $ticket = Ticket::find($booking->ticket_id);
        $displayName = $booking->ticket_name . ' - ' . $booking->ticket_category;

        // Buat Nomor Invoice unik (Contoh: INV-BACT-1-1785...)
        $invoiceNumber = 'INV-BACT-' . $booking->id . '-' . time();

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
            ],
            'payment' => [
                'payment_due_date' => 1440 // Expired VA/Link dalam menit (24 jam)
            ],
            'customer' => [
                'id' => (string) $user->id,
                'name' => $booking->full_name,
                'email' => $booking->gmail_account,
                'phone' => $booking->whatsapp_number,
                'address' => $booking->institution_name . ', ' . $booking->institution_city,
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
            Log::error('DOKU checkout request exception', [
                'booking_id'   => $booking->id,
                'base_url'     => $baseUrl,
                'error'        => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal memproses ke gerbang pembayaran DOKU. Silakan coba lagi.');
        }

        $dokuResult = $response->json();

        // Cek jika berhasil dapat link pembayaran (payment_url) dari DOKU
        if ($response->successful() && isset($dokuResult['response']['payment']['url'])) {
            $paymentUrl = $dokuResult['response']['payment']['url'];

            // Simpan invoice_number, link, dan waktu expired link agar bisa di-reuse saat "Lanjutkan Pembayaran".
            $paymentExpiredAt = $this->parsePaymentExpiredDate($dokuResult);
            $booking->update([
                'invoice_number'      => $invoiceNumber,
                'payment_url'         => $paymentUrl,
                'payment_expired_at'  => $paymentExpiredAt,
            ]);

            return view('booking.checkout', compact('booking', 'ticket', 'displayName', 'paymentUrl'));
        }

        // Jika gagal konek ke DOKU
        Log::error('DOKU checkout failed', [
            'booking_id' => $booking->id,
            'base_url'   => $baseUrl,
            'http_status' => $response->status(),
            'response'   => $dokuResult,
        ]);
        return back()->with('error', 'Gagal memproses ke gerbang pembayaran DOKU. Silakan coba lagi.');
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

        $booking = $this->syncBookingFromCallback($request, $user, $booking);

        $isProduction = filter_var(config('services.doku.is_production', false), FILTER_VALIDATE_BOOL);
        $shouldAutoFinalize = !$isProduction && ((int) $request->query('simulated_paid', 0) === 1 || $booking->status === 'pending');

        if ($shouldAutoFinalize && $booking->status !== 'paid') {
            $booking->update([
                'status'  => 'paid',
                'paid_at' => $booking->paid_at ?? now(),
            ]);
            app(TicketNotificationService::class)->sendTicketPaid($booking);
        }

        return redirect()->route('booking.index', array_filter([
            'transaction_status' => $request->query('transaction_status'),
            'payment_status' => $request->query('payment_status'),
            'status' => $request->query('status'),
            'invoice_number' => $request->query('invoice_number') ?? $request->query('invoice_no') ?? $request->query('invoice'),
            'order_id' => $request->query('order_id'),
            'simulated_paid' => $request->query('simulated_paid'),
        ], fn ($value) => $value !== null && $value !== ''));
    }

    private function isProfileComplete($user): bool
    {
        return !empty($user->email_verified_at) &&
               !empty($user->name) &&
               !empty($user->nik) &&
               !empty($user->gender);
    }

    /**
     * Cek apakah booking pending sudah harus dibatalkan otomatis:
     * melewati batas waktu pembayaran (24 jam sejak dibuat) atau tiket sudah tidak berlaku.
     */
    private function isBookingExpired(TicketBooking $booking): bool
    {
        if ($booking->created_at && $booking->created_at->lt(now()->subHours(24))) {
            return true;
        }

        $ticket = $booking->ticket;
        if ($ticket && $ticket->end_date && $ticket->end_date->lt(now())) {
            return true;
        }

        return false;
    }

    /**
     * Batalkan booking pending secara otomatis dan kembalikan kuota tiket.
     */
    private function cancelExpiredBooking(TicketBooking $booking): void
    {
        if ($booking->ticket_id) {
            \App\Models\Ticket::where('id', $booking->ticket_id)->increment('quota');
        }

        $noteLine = now()->format('d M Y H:i') . ' — Dibatalkan otomatis oleh sistem (melewati batas waktu pembayaran / tiket kedaluwarsa)';
        $booking->notes = trim(($booking->notes ? $booking->notes . "\n" : '') . $noteLine);
        $booking->notes_updated_at = now();
        $booking->cancelled_at = now();
        $booking->status = 'cancelled';
        $booking->save();
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
                return Carbon::parse($raw);
            } catch (\Throwable $e) {
                // ignore, fallback di bawah
            }
        }

        return now()->addHours(24);
    }

    /**
     * Pastikan booking punya checkin_token unik (dipakai di QR check-in).
     */
    private function ensureCheckinToken(TicketBooking $booking): TicketBooking
    {
        if (empty($booking->checkin_token)) {
            $booking->update([
                'checkin_token' => 'BACT-' . $booking->id . '-' . strtoupper(Str::random(10)),
            ]);
            $booking->refresh();
        }

        return $booking;
    }

    private function syncBookingFromCallback(Request $request, $user, ?TicketBooking $existingBooking): ?TicketBooking
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
            return $existingBooking;
        }

        $targetBooking = null;

        if ($incomingInvoice) {
            $targetBooking = TicketBooking::where('user_id', $user->id)
                ->where('invoice_number', $incomingInvoice)
                ->first();
        }

        if (!$targetBooking) {
            $targetBooking = $existingBooking;
        }

        if ($targetBooking && $isPaidCallback && $targetBooking->status !== 'paid') {
            $targetBooking->update([
                'status'  => 'paid',
                'paid_at' => $targetBooking->paid_at ?? now(),
            ]);
            app(TicketNotificationService::class)->sendTicketPaid($targetBooking);
        }

        return $targetBooking ?: $existingBooking;
    }
}