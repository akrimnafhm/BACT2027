<?php

namespace App\Http\Controllers;

use App\Models\HotelRoom;
use App\Models\HotelReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HotelController extends Controller
{
    /**
     * Tampilan Utama Manajemen Reservasi Hotel (Dengan Filter & Pagination)
     */
    public function index(Request $request)
    {
        $search   = $request->input('search');
        $roomType = $request->input('room_type');
        $status   = $request->input('status');

        $query = \App\Models\HotelReservation::with(['user', 'hotelRoom'])->latest();

        // Filter Pencarian (Kode, Nama, Email)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter Tipe Kamar
        if (!empty($roomType)) {
            $query->whereHas('hotelRoom', function ($q) use ($roomType) {
                $q->where('room_type', $roomType);
            });
        }

        // Filter Status
        if (!empty($status)) {
            $query->where('status', $status);
        }

        $reservations = $query->paginate(10)->withQueryString();
        $roomTypes = \App\Models\HotelRoom::select('room_type')->distinct()->pluck('room_type');

        return view('admin.hotels', compact('reservations', 'search', 'roomType', 'status', 'roomTypes'));
    }

    /**
     * EXPORT EXCEL (.CSV) UNTUK DATA RESERVASI HOTEL
     */
    public function exportReservations(Request $request)
    {
        $search   = $request->input('search');
        $roomType = $request->input('room_type');
        $status   = $request->input('status');

        $query = \App\Models\HotelReservation::with(['user', 'hotelRoom'])->oldest();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($roomType)) {
            $query->whereHas('hotelRoom', function ($q) use ($roomType) {
                $q->where('room_type', $roomType);
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $reservations = $query->get();
        $fileName = 'Data-Reservasi-Hotel-BACT2027-' . date('Y-m-d-His') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($reservations) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF"); // BOM untuk UTF-8 Excel

            fputcsv($file, [
                'Kode Booking',
                'Tanggal Pesan',
                'Nama Pemesan',
                'Email',
                'Tipe Kamar',
                'Check-In',
                'Check-Out',
                'Total Malam',
                'Total Tagihan',
                'Status',
                'Catatan Khusus'
            ], ',');

            foreach ($reservations as $row) {
                fputcsv($file, [
                    $row->booking_code,
                    $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '-',
                    $row->user->name ?? 'User Terhapus',
                    $row->user->email ?? '-',
                    $row->hotelRoom->room_type ?? 'Kamar Dihapus',
                    $row->check_in,
                    $row->check_out,
                    $row->total_nights,
                    $row->total_price,
                    strtoupper($row->status),
                    $row->special_request ?? '-'
                ], ',');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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