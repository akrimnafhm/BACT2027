<?php

namespace App\Http\Controllers;

use App\Models\HotelRoom;
use App\Models\HotelReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class HotelBookingController extends Controller
{
    public function index()
    {
        // 1. Cek status pengguna saat membuka halaman hotel
        if (!Auth::check()) {
            $status = 'guest';
        } elseif (empty(Auth::user()->nik) || empty(Auth::user()->phone_number)) {
            $status = 'incomplete';
        } else {
            $status = 'ready';
        }

        $hotels = HotelRoom::oldest()->get();

        // 2. Kirim $status ke view
        return view('hotels.index', compact('hotels', 'status'));
    }

    public function create($id)
    {
        $hotel = HotelRoom::where('is_active', true)->findOrFail($id);

        if ($hotel->quota <= 0) {
            return back()->with('error', 'Maaf, kuota untuk tipe kamar ini sudah habis.');
        }

        return view('hotels.book', compact('hotel'));
    }

    /**
     * Simpan reservasi kamar hotel peserta
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

        $room = HotelRoom::findOrFail($id);

        // 2. Hitung durasi menginap (malam) & total harga
        $checkInDate = \Carbon\Carbon::parse($request->check_in);
        $checkOutDate = \Carbon\Carbon::parse($request->check_out);
        $nights = $checkInDate->diffInDays($checkOutDate);
        $totalPrice = $room->price_per_night * $nights;

        // 3. LOGIKA OVERWRITE: Timpa reservasi pending lama milik user ini (jika ada)
        // Jika belum punya yang pending, otomatis buat baris baru.
        $reservation = HotelReservation::updateOrCreate(
            [
                'user_id' => $user->id,
                'status' => 'pending', // Hanya menargetkan yang belum dibayar
            ],
            [
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

        // 4. Redirect langsung ke halaman pembayaran Midtrans
        return redirect()->route('hotels.checkout', $reservation->id);
    }
}