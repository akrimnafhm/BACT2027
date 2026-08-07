<?php

namespace App\Http\Controllers;

use App\Models\HotelRoom;
use App\Models\HotelReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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

        // 2. Hitung durasi menginap (malam) & total harga
        $checkInDate = \Carbon\Carbon::parse($request->check_in);
        $checkOutDate = \Carbon\Carbon::parse($request->check_out);
        $nights = $checkInDate->diffInDays($checkOutDate);
        $totalPrice = $room->price_per_night * $nights;

        // 3. Generate kode booking unik (misal: HTL-20270118-AB12)
        $bookingCode = 'HTL-' . $checkInDate->format('Ymd') . '-' . strtoupper(Str::random(4));

        // 4. LOGIKA OVERWRITE: Timpa reservasi pending lama milik user ini (jika ada)
        $reservation = HotelReservation::updateOrCreate(
            [
                'user_id' => $user->id,
                'status' => 'pending', // Hanya menargetkan yang belum dibayar
            ],
            [
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
            ]
        );

        // 5. Redirect langsung ke halaman pembayaran DOKU
        return redirect()->route('hotels.checkout', $reservation->id);
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

        // Buat Nomor Invoice unik (Contoh: INV-HTL-1-1785...)
        $invoiceNumber = 'INV-HTL-' . $reservation->id . '-' . time();

        // -------------------------------------------------------------
        // PEMANGGILAN API DOKU CHECKOUT (DIRECT VIA LARAVEL HTTP CLIENT)
        // -------------------------------------------------------------
        $clientId = env('DOKU_CLIENT_ID');
        $secretKey = env('DOKU_SECRET_KEY');
        $isProduction = env('DOKU_IS_PRODUCTION', false);

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
                'notification_url' => env('DOKU_NOTIFICATION_URL', url('/api/doku/notification')),
            ],
            'payment' => [
                'payment_due_date' => 60 // Expired VA/Link dalam menit (1 jam)
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
        $response = Http::withHeaders([
            'Client-Id' => $clientId,
            'Request-Id' => $requestId,
            'Request-Timestamp' => $requestTimestamp,
            'Signature' => $signature,
            'Content-Type' => 'application/json',
        ])->send('POST', $baseUrl . $requestTarget, [
            'body' => $jsonBody
        ]);

        $dokuResult = $response->json();

        // Cek jika berhasil dapat link pembayaran (payment_url) dari DOKU
        if ($response->successful() && isset($dokuResult['response']['payment']['url'])) {
            $paymentUrl = $dokuResult['response']['payment']['url'];

            // Simpan invoice_number ke tabel agar bisa dilacak saat lunas
            $reservation->update(['invoice_number' => $invoiceNumber]);

            return view('hotels.checkout', compact('reservation', 'room', 'paymentUrl'));
        }

        // Jika gagal konek ke DOKU
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

        $isProduction = filter_var(env('DOKU_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN);
        $shouldAutoFinalize = !$isProduction && ((int) $request->query('simulated_paid', 0) === 1 || $reservation->status === 'pending');

        if ($shouldAutoFinalize && $reservation->status !== 'paid') {
            $reservation->update(['status' => 'paid']);
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
        }

        return $targetReservation ?: $existingReservation;
    }
}
