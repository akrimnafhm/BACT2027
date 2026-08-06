<?php

namespace App\Http\Controllers;

use App\Models\HotelRoom;
use App\Models\HotelReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HotelController extends Controller
{
    /**
     * Tampilan Utama Manajemen Hotel
     */
    /**
     * Tampilan Utama Manajemen Hotel (Tipe Kamar & Reservasi)
     */
    public function index()
    {
        $hotels = HotelRoom::oldest()->get();
        
        // Ambil daftar reservasi beserta data user dan kamar terkait
        $reservations = HotelReservation::with(['user', 'hotelRoom'])->latest()->get();

        // Pastikan 'reservations' masuk ke dalam compact()
        return view('admin.hotels', compact('hotels', 'reservations'));
    }

    /**
     * Simpan Tipe Kamar Baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'room_type'       => 'required|string|max:255',
            'price_per_night' => 'required|numeric|min:0',
            'quota'           => 'required|integer|min:0',
            'description'     => 'nullable|string',
            'facilities'      => 'nullable|string',
            'image'           => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $imagePath = $request->file('image')->store('hotels', 'public');

        HotelRoom::create([
            'room_type'       => $request->input('room_type'),
            'price_per_night' => $request->input('price_per_night'),
            'quota'           => $request->input('quota'),
            'description'     => $request->input('description'),
            'facilities'      => $request->input('facilities'),
            'image'           => $imagePath,
            'is_active'       => true,
        ]);

        return back()->with('success', 'Tipe kamar baru berhasil ditambahkan!');
    }

    /**
     * Update Tipe Kamar
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'room_type'       => 'required|string|max:255',
            'price_per_night' => 'required|numeric|min:0',
            'quota'           => 'required|integer|min:0',
            'description'     => 'nullable|string',
            'facilities'      => 'nullable|string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $hotel = HotelRoom::findOrFail($id);
        
        $data = [
            'room_type'       => $request->input('room_type'),
            'price_per_night' => $request->input('price_per_night'),
            'quota'           => $request->input('quota'),
            'description'     => $request->input('description'),
            'facilities'      => $request->input('facilities'),
        ];

        if ($request->hasFile('image')) {
            if ($hotel->image && Storage::disk('public')->exists($hotel->image)) {
                Storage::disk('public')->delete($hotel->image);
            }
            $data['image'] = $request->file('image')->store('hotels', 'public');
        }

        $hotel->update($data);

        return back()->with('success', 'Data kamar hotel berhasil diperbarui!');
    }

    /**
     * Hapus Tipe Kamar
     */
    public function destroy($id)
    {
        $hotel = HotelRoom::findOrFail($id);

        if ($hotel->image && Storage::disk('public')->exists($hotel->image)) {
            Storage::disk('public')->delete($hotel->image);
        }

        $hotel->delete();

        return back()->with('success', 'Tipe kamar berhasil dihapus!');
    }

    /**
     * Toggle Status Aktif / Tutup Kamar
     */
    public function toggle($id)
    {
        $hotel = HotelRoom::findOrFail($id);
        $hotel->is_active = !$hotel->is_active;
        $hotel->save();

        return back()->with('success', 'Status ketersediaan kamar berhasil diubah!');
    }

    /**
     * Update Status Reservasi Peserta (pending -> paid / cancelled)
     */
    public function updateReservationStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,cancelled',
        ]);

        $reservation = HotelReservation::findOrFail($id);
        $oldStatus = $reservation->status;
        $newStatus = $request->input('status');

        // Jika dibatalkan (cancelled), kembalikan kuota kamar +1
        if ($oldStatus !== 'cancelled' && $newStatus === 'cancelled') {
            if ($reservation->hotelRoom) {
                $reservation->hotelRoom->increment('quota', 1);
            }
        }

        // Jika sebelumnya cancelled lalu diaktifkan lagi (paid/pending), kurangi kuota -1
        if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
            if ($reservation->hotelRoom && $reservation->hotelRoom->quota > 0) {
                $reservation->hotelRoom->decrement('quota', 1);
            }
        }

        $reservation->update(['status' => $newStatus]);

        return back()->with('success', 'Status reservasi hotel berhasil diperbarui!');
    }
}