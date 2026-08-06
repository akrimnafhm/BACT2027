<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\HotelRoom;
use App\Models\TicketBooking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    /**
     * Menampilkan halaman daftar pemesanan tiket sesuai status user.
     */
    public function index()
    {
        $hotels = HotelRoom::where('is_active', true)->get();
        $now = Carbon::now();

        // 1. QUERY TIKET AKTIF (Waktu Valid & is_active = 1)
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

        // Gabungkan nama tiket dan kategori untuk Tampilan di UI
        foreach ($activeTickets as $ticket) {
            $ticket->display_name = $ticket->ticket_name . ' - ' . $ticket->ticket_category;
        }

        // --- SKENARIO 1: TAMU (GUEST) ---
        if (!Auth::check()) {
            return view('booking.index', [
                'status'  => 'guest',
                'tickets' => $activeTickets
            ]);
        }

        $user = Auth::user();

        // SATPAM BACKEND: Tolak proses jika profil belum 100% lengkap
        $isProfileComplete = !empty($user->email_verified_at) &&
                             !empty($user->phone_verified_at) &&
                             !empty($user->name) &&
                             !empty($user->nik) &&
                             !empty($user->gender);

        if (!$isProfileComplete) {
            return redirect()->route('profile.edit')
                ->withErrors(['error' => 'Mohon lengkapi data diri, serta verifikasi akun Anda terlebih dahulu sebelum pesan tiket.']);
        }

        // Cari transaksi terakhir milik user ini
        $existingBooking = TicketBooking::where('user_id', $user->id)
                            ->latest()
                            ->first();

        // --- SKENARIO 3: SUDAH BAYAR / LUNAS (E-TICKET) ---
        if ($existingBooking && $existingBooking->status === 'paid') {
            $bookedTicket = Ticket::find($existingBooking->ticket_id);
            
            // Tampilkan nama utuh gabungan gelombang & kategori pada E-Ticket
            $bookedTicket->display_name = $bookedTicket->ticket_name . ' - ' . $bookedTicket->ticket_category;
            
            return view('booking.index', [
                'status'       => 'post_purchase',
                'user'         => $user,
                'booking'      => $existingBooking,
                'bookedTicket' => $bookedTicket,
                'hotels'       => $hotels
            ]);
        }

        // --- SKENARIO 4: PENDING / SIAP PESAN ---
        return view('booking.index', [
            'status'          => 'ready',
            'user'            => $user,
            'tickets'         => $activeTickets,
            'existingBooking' => $existingBooking ?? null
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
        if ($ticket->quota <= 0) {
            return back()->with('error', 'Maaf, kuota tiket ini sudah habis.');
        }

        // LOCK DATA: Menyimpan snapshot harga, jenis tiket, dan identitas peserta pada saat transaksi
        $booking = TicketBooking::updateOrCreate(
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

        $ticket = Ticket::find($booking->ticket_id);
        $displayName = $booking->ticket_name . ' - ' . $booking->ticket_category;
        
        // Buat Nomor Invoice unik (Contoh: INV-BACT-1-1785...)
        $invoiceNumber = 'INV-BACT-' . $booking->id . '-' . time();

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
                'amount' => $booking->amount,
                'invoice_number' => $invoiceNumber,
                'currency' => 'IDR',
                'callback_url' => route('booking.index'), // URL kembali setelah bayar
            ],
            'payment' => [
                'payment_due_date' => 60 // Expired VA/Link dalam menit (1 jam)
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
            $booking->update(['invoice_number' => $invoiceNumber]);

            return view('booking.checkout', compact('booking', 'ticket', 'displayName', 'paymentUrl'));
        }

        // Jika gagal konek ke DOKU
        return back()->with('error', 'Gagal memproses ke gerbang pembayaran DOKU. Silakan coba lagi.');
    }
}