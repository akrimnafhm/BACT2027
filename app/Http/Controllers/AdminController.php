<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketBooking;
use App\Models\HotelRoom;
use App\Services\TicketNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * Menampilkan halaman Ringkasan Dashboard.
     */
    public function dashboard()
    {
        // 1. Total Uang Masuk & Peserta Lunas (Card Atas)
        $paidBookings = TicketBooking::where('status', 'paid')->get();
        
        $totalRevenue = $paidBookings->sum('amount');
        $totalPaidParticipants = $paidBookings->count();
        $totalPendingBookings = TicketBooking::where('status', 'pending')->count();

        // 2. Siapkan 6 Kategori Tiket persis sesuai UI Dashboard
        $categories = [
            'Basic'                      => ['paid' => 0, 'revenue' => 0],
            'Advance'                    => ['paid' => 0, 'revenue' => 0],
            'Basic - Advance'            => ['paid' => 0, 'revenue' => 0],
            'Online'                     => ['paid' => 0, 'revenue' => 0],
            'Workshop'                   => ['paid' => 0, 'revenue' => 0],
            'Basic - Advance + Workshop' => ['paid' => 0, 'revenue' => 0],
        ];

        // 3. Ambil semua data master tiket dari database
        $allTickets = Ticket::all();

        /**
         * 4. COCOKKAN LANGSUNG DARI KOLOM `ticket_category`
         * Tidak ada lagi tebak-tebakan nama/sinonim. Murni membaca kolom ticket_category!
         */
        foreach ($paidBookings as $booking) {
            $ticket = $allTickets->firstWhere('id', $booking->ticket_id);
            if (!$ticket) continue;

            $category = trim($ticket->ticket_category);
            $amount = $booking->amount ?: ($ticket->price ?? 0);

            if ($category === 'Basic') {
                $categories['Basic']['paid']    += 1;
                $categories['Basic']['revenue'] += $amount;
            } elseif ($category === 'Advance') {
                $categories['Advance']['paid']    += 1;
                $categories['Advance']['revenue'] += $amount;
            } elseif ($category === 'Basic-Advance' || $category === 'Basic - Advance') {
                $categories['Basic - Advance']['paid']    += 1;
                $categories['Basic - Advance']['revenue'] += $amount;
            } elseif ($category === 'Online') {
                $categories['Online']['paid']    += 1;
                $categories['Online']['revenue'] += $amount;
            } elseif ($category === 'Workshop') {
                $categories['Workshop']['paid']    += 1;
                $categories['Workshop']['revenue'] += $amount;
            } elseif ($category === 'Basic-Advance + Workshop' || $category === 'Basic - Advance + Workshop') {
                $categories['Basic - Advance + Workshop']['paid']    += 1;
                $categories['Basic - Advance + Workshop']['revenue'] += $amount;
            }
        }

        return view('admin.dashboard', compact(
            'totalRevenue',
            'totalPaidParticipants',
            'totalPendingBookings',
            'categories'
        ));
    }

    // ==========================================
    // 1. KELOLA KUOTA & HARGA (TIKET & HOTEL)
    // ==========================================

    /**
     * Menampilkan halaman Kelola Kuota & Harga (Tiket Simposium + Kamar Hotel).
     */
    public function tickets(): View
    {
        $tickets = Ticket::orderBy('created_at', 'desc')->get();
        $hotels  = HotelRoom::oldest()->get();

        return view('admin.tickets', compact('tickets', 'hotels'));
    }

    /**
     * Menampilkan form tambah tiket baru.
     */
    public function createTicket(): View
    {
        return view('admin.tickets.create');
    }

    /**
     * Menyimpan data tiket baru ke database.
     */
    public function storeTicket(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'ticket_name'     => 'required|string|max:100',
            'ticket_category' => 'required|string|in:Basic,Advance,Basic-Advance,Online,Workshop,Basic-Advance + Workshop',
            'price'           => 'required|numeric|min:0',
            'quota'           => 'required|integer|min:1',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
        ], [
            'end_date.after_or_equal' => 'Tanggal berakhir tidak boleh lebih awal dari tanggal mulai.'
        ]);

        Ticket::create([
            'ticket_name'     => $request->ticket_name,
            'ticket_category' => $request->ticket_category,
            'price'           => $request->price,
            'quota'           => $request->quota,
            'start_date'      => $request->start_date ?: null,
            'end_date'        => $request->end_date ?: null,
            'is_active'       => true,
        ]);

        return redirect()->route('admin.tickets.index')
                         ->with('success', 'Tiket baru berhasil ditambahkan!');
    }

    /**
     * Menampilkan form edit tiket.
     */
    public function editTicket(int $id): View
    {
        $ticket = Ticket::findOrFail($id);
        return view('admin.tickets.edit', compact('ticket'));
    }

    /**
     * Menyimpan perubahan data tiket ke database.
     */
    public function updateTicket(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'ticket_name'     => 'required|string|max:100',
            'ticket_category' => 'required|string|in:Basic,Advance,Basic-Advance,Online,Workshop,Basic-Advance + Workshop',
            'price'           => 'required|numeric|min:0',
            'quota'           => 'required|integer|min:0',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
        ], [
            'end_date.after_or_equal' => 'Tanggal berakhir tidak boleh lebih awal dari tanggal mulai.'
        ]);

        $ticket = Ticket::findOrFail($id);
        $ticket->update([
            'ticket_name'     => $request->ticket_name,
            'ticket_category' => $request->ticket_category,
            'price'           => $request->price,
            'quota'           => $request->quota,
            'start_date'      => $request->start_date ?: null,
            'end_date'        => $request->end_date ?: null,
        ]);

        return redirect()->route('admin.tickets.index')
                         ->with('success', 'Data tiket berhasil diperbarui!');
    }

    /**
     * Menghapus tiket dari database.
     */
    public function destroyTicket(int $id): \Illuminate\Http\RedirectResponse
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return redirect()->route('admin.tickets.index')
                         ->with('success', 'Tiket berhasil dihapus!');
    }

    /**
     * Mengubah status ON/OFF tiket secara asynchronous (AJAX).
     */
    public function toggleTicketStatus(int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->is_active = !$ticket->is_active;
        $ticket->save();

        return response()->json([
            'success'   => true,
            'is_active' => $ticket->is_active,
            'message'   => 'Status tiket berhasil diperbarui!'
        ]);
    }

    // ==========================================
    // 2. CRUD KAMAR HOTEL (DI DALAM KUOTA & HARGA)
    // ==========================================

    /**
     * Simpan Tipe Kamar Baru dari Halaman Kuota & Harga
     */
    public function storeHotel(Request $request): \Illuminate\Http\RedirectResponse
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

        return redirect()->route('admin.tickets.index')
                         ->with('success', 'Tipe kamar baru berhasil ditambahkan!');
    }

    /**
     * Update Data Kamar Hotel
     */
    public function updateHotel(Request $request, int $id): \Illuminate\Http\RedirectResponse
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

        return redirect()->route('admin.tickets.index')
                         ->with('success', 'Data kamar hotel berhasil diperbarui!');
    }

    /**
     * Hapus Tipe Kamar Hotel
     */
    public function destroyHotel(int $id): \Illuminate\Http\RedirectResponse
    {
        $hotel = HotelRoom::findOrFail($id);

        if ($hotel->image && Storage::disk('public')->exists($hotel->image)) {
            Storage::disk('public')->delete($hotel->image);
        }

        $hotel->delete();

        return redirect()->route('admin.tickets.index')
                         ->with('success', 'Tipe kamar berhasil dihapus!');
    }

    /**
     * Mengubah status ON/OFF kamar hotel secara asynchronous (AJAX).
     */
    public function toggleHotelStatus(int $id): JsonResponse
    {
        $hotel = HotelRoom::findOrFail($id);
        $hotel->is_active = !$hotel->is_active;
        $hotel->save();

        return response()->json([
            'success'   => true,
            'is_active' => $hotel->is_active,
            'message'   => 'Status kamar hotel berhasil diperbarui!'
        ]);
    }

    // ==========================================
    // 3. KELOLA TIKET PESERTA SIMPOSIUM
    // ==========================================

    /**
     * HALAMAN TIKET PESERTA (FILTER 1 BARIS & PAGINATION 10 DATA)
     */
    public function participants(Request $request)
    {
        $search   = $request->input('search');
        $category = $request->input('category');
        $wave     = $request->input('wave');
        $status   = $request->input('status');

        $query = \App\Models\TicketBooking::with('ticket')->latest();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('name_with_title', 'like', "%{$search}%")
                  ->orWhere('gmail_account', 'like', "%{$search}%")
                  ->orWhere('whatsapp_number', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('institution_name', 'like', "%{$search}%");
            });
        }

        if (!empty($category)) {
            $query->whereHas('ticket', function ($q) use ($category) {
                $q->where('ticket_category', $category);
            });
        }

        if (!empty($wave)) {
            $query->whereHas('ticket', function ($q) use ($wave) {
                $q->where('ticket_name', 'like', "%{$wave}%");
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $participants = $query->paginate(10)->withQueryString();

        $waves = \App\Models\Ticket::select('ticket_name')->distinct()->pluck('ticket_name');
        $allTickets = \App\Models\Ticket::all();

        return view('admin.participants', compact('participants', 'waves', 'allTickets', 'search', 'category', 'wave', 'status'));
    }

    /**
     * EXPORT EXCEL (.CSV) KOMA STANDAR & BERSIH DARI SIMBOL ANEH
     */
    public function exportParticipants(Request $request)
    {
        $search   = $request->input('search');
        $category = $request->input('category');
        $wave     = $request->input('wave');
        $status   = $request->input('status');

        $query = \App\Models\TicketBooking::with('ticket')->oldest();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('name_with_title', 'like', "%{$search}%")
                  ->orWhere('gmail_account', 'like', "%{$search}%")
                  ->orWhere('whatsapp_number', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('institution_name', 'like', "%{$search}%");
            });
        }

        if (!empty($category)) {
            $query->whereHas('ticket', function ($q) use ($category) {
                $q->where('ticket_category', $category);
            });
        }

        if (!empty($wave)) {
            $query->whereHas('ticket', function ($q) use ($wave) {
                $q->where('ticket_name', 'like', "%{$wave}%");
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $bookings = $query->get();
        $fileName = 'Data-Peserta-BACT2026-' . date('Y-m-d-His') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($bookings) {
            $file = fopen('php://output', 'w');
            
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'ID Pesanan',
                'Gelombang Tiket',
                'Kategori Tiket',
                'Nama Lengkap (KTP)',
                'Nama Sertifikat & Gelar',
                'Email (Gmail)',
                'No WhatsApp',
                'NIK',
                'Nominal Bayar',
                'Status Pembayaran',
                'Nama Instansi / RS',
                'Kota Instansi',
                'Provinsi Instansi',
                'Profesi Medis',
                'Email Plataran Sehat',
                'Tanggal Daftar'
            ], ',');

            foreach ($bookings as $row) {
                fputcsv($file, [
                    $row->id,
                    $row->ticket->ticket_name ?? 'Tiket BACT',
                    $row->ticket->ticket_category ?? 'Umum',
                    $row->full_name,
                    $row->name_with_title ?: $row->full_name,
                    $row->gmail_account,
                    "'" . $row->whatsapp_number,
                    "'" . $row->nik,
                    $row->amount,
                    strtoupper($row->status),
                    $row->institution_name,
                    $row->institution_city,
                    $row->institution_province,
                    $row->profession,
                    $row->plataran_sehat_email,
                    $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '-'
                ], ',');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * UPDATE STATUS & DATA PESERTA (AKSI EDIT)
     */
    public function updateParticipantStatus(Request $request, $id)
    {
        $request->validate([
            'status'          => 'required|in:paid,pending',
            'full_name'       => 'required|string|max:255',
            'name_with_title' => 'required|string|max:255',
            'whatsapp_number' => 'required|string|max:25',
        ]);

        $booking = \App\Models\TicketBooking::findOrFail($id);
        $booking->status          = $request->status;
        $booking->full_name       = $request->full_name;
        $booking->name_with_title = $request->name_with_title;
        $booking->whatsapp_number = $request->whatsapp_number;
        $booking->save();

        // Kirim notifikasi WA & Email jika status diubah menjadi LUNAS
        if ($booking->status === 'paid') {
            app(TicketNotificationService::class)->sendTicketPaid($booking);
        }

        return back()->with('success', 'Data dan status peserta berhasil diperbarui!');
    }

    /**
     * TAMBAH PESERTA MANUAL (PESERTA TITIPAN / ADMIN ENTRY)
     */
    public function storeParticipantManual(Request $request)
    {
        $request->validate([
            'ticket_id'       => 'required|exists:tickets,id',
            'full_name'       => 'required|string|max:255',
            'name_with_title' => 'required|string|max:255',
            'gmail_account'   => 'required|email|max:255',
            'whatsapp_number' => 'required|string|max:25',
            'nik'             => 'required|string|max:20',
            'institution_name'=> 'required|string|max:255',
            'profession'      => 'required|string|max:100',
            'status'          => 'required|in:paid,pending'
        ]);

        $ticket = \App\Models\Ticket::findOrFail($request->ticket_id);

        \App\Models\TicketBooking::create([
            'user_id'              => auth()->id(),
            'ticket_id'            => $ticket->id,
            'full_name'            => $request->full_name,
            'name_with_title'      => $request->name_with_title,
            'gmail_account'        => $request->gmail_account,
            'whatsapp_number'      => $request->whatsapp_number,
            'nik'                  => $request->nik,
            'amount'               => $ticket->price ?? 0,
            'status'               => $request->status,
            'institution_name'     => $request->institution_name,
            'institution_city'     => $request->institution_city ?? 'Kota Instansi',
            'institution_province' => $request->institution_province ?? 'Provinsi Instansi',
            'profession'           => $request->profession,
            'plataran_sehat_email' => $request->plataran_sehat_email ?? $request->gmail_account,
            'payment_method'       => 'MANUAL_ADMIN'
        ]);

        // Kirim notifikasi jika peserta manual langsung LUNAS
        if ($request->status === 'paid') {
            $newBooking = \App\Models\TicketBooking::where('gmail_account', $request->gmail_account)
                            ->where('status', 'paid')
                            ->latest()
                            ->first();

            if ($newBooking) {
                app(TicketNotificationService::class)->sendTicketPaid($newBooking);
            }
        }

        return back()->with('success', 'Peserta manual baru berhasil ditambahkan ke sistem!');
    }

    // ==========================================
    // 4. QR CHECK-IN PESERTA
    // ==========================================

    /**
     * Halaman Scanner QR Check-in (2 Tab: Scan/Input & Peserta Sudah Check-in).
     */
    public function checkin(Request $request): View
    {
        $activeTab = $request->input('tab', 'scan') === 'checked' ? 'checked' : 'scan';

        return view('admin.checkin', [
            'activeTab'             => $activeTab,
            'checkedInParticipants' => $this->checkedInParticipants(),
        ]);
    }

    /**
     * Proses hasil pindai QR / input manual: cari peserta berdasarkan checkin_token.
     */
    public function scanCheckin(Request $request): View
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $token = trim($request->token);
        $booking = \App\Models\TicketBooking::with('ticket')
                    ->where('checkin_token', $token)
                    ->first();

        $data = [
            'activeTab'             => 'scan',
            'checkedInParticipants' => $this->checkedInParticipants(),
        ];

        if (!$booking) {
            return view('admin.checkin', $data + ['error' => 'Kode QR tidak ditemukan. Pastikan QR yang dipindai adalah QR tiket peserta BACT.']);
        }

        if ($booking->status !== 'paid') {
            return view('admin.checkin', $data + [
                'error'   => 'Peserta "' . $booking->full_name . '" belum melakukan pembayaran. Check-in tidak dapat dilakukan.',
                'booking' => $booking,
            ]);
        }

        return view('admin.checkin', $data + ['booking' => $booking]);
    }

    /**
     * Konfirmasi Check-in peserta (menandai waktu check-in).
     */
    public function confirmCheckin(Request $request, $id): \Illuminate\Http\RedirectResponse
    {
        $booking = \App\Models\TicketBooking::findOrFail($id);

        if ($booking->status !== 'paid') {
            return back()->with('error', 'Peserta belum melakukan pembayaran, tidak dapat check-in.');
        }

        if ($booking->checked_in_at) {
            return back()->with('error', 'Peserta "' . $booking->full_name . '" sudah check-in pada ' . $booking->checked_in_at->format('d M Y H:i') . '.');
        }

        $booking->update(['checked_in_at' => now()]);

        return redirect()->route('admin.checkin.index')
                         ->with('success', 'Check-in berhasil untuk ' . $booking->full_name . '.');
    }

    /**
     * Daftar peserta yang sudah check-in (urutan terbaru).
     */
    private function checkedInParticipants(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return \App\Models\TicketBooking::with('ticket')
                    ->whereNotNull('checked_in_at')
                    ->latest('checked_in_at')
                    ->paginate(10)
                    ->withQueryString();
    }
}