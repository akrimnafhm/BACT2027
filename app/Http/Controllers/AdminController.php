<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketBooking;
use App\Models\HotelRoom;
use App\Models\SiteSetting;
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

        // Mode situs (normal / maintenance) untuk switch di dashboard.
        $siteMode = SiteSetting::value('site_mode', 'normal');

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
            'categories',
            'siteMode'
        ));
    }

    /**
     * Toggle mode situs: normal <-> maintenance.
     * Saat maintenance aktif, hanya halaman admin yang bisa diakses;
     * halaman publik menampilkan informasi maintenance.
     */
    public function toggleMaintenance(Request $request): \Illuminate\Http\RedirectResponse
    {
        $current = SiteSetting::value('site_mode', 'normal');
        $next = $current === 'maintenance' ? 'normal' : 'maintenance';

        SiteSetting::updateOrCreate(
            ['key' => 'site_mode'],
            ['value' => $next]
        );

        $message = $next === 'maintenance'
            ? 'Mode maintenance AKTIF. Website publik kini diblokir, hanya halaman admin yang dapat diakses.'
            : 'Mode maintenance dimatikan. Website kembali berjalan normal.';

        return back()->with('success', $message);
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
    // 1B. GURUP WHATSAPP PESERTA (LINK PER JENIS TIKET)
    // ==========================================

    /**
     * Halaman pengaturan link grup WA per kategori tiket.
     */
    public function groupLinks(): View
    {
        $categories = \App\Models\Ticket::select('ticket_category')->distinct()->pluck('ticket_category');
        $links = \App\Models\WaGroupLink::pluck('wa_group_link', 'ticket_category');

        $groups = [];
        foreach ($categories as $category) {
            $normalized = \App\Models\WaGroupLink::normalizeCategory($category);
            $groups[$normalized] = [
                'category' => $normalized,
                'name'     => $normalized,
                'link'     => $links[$normalized] ?? null,
            ];
        }

        return view('admin.groups', ['groups' => collect($groups)->values()]);
    }

    /**
     * Simpan link grup WA untuk semua kategori sekaligus.
     */
    public function updateGroupLinks(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'links'   => 'required|array',
            'links.*' => 'nullable|string|max:500',
        ]);

        $updated = 0;
        foreach ($request->input('links', []) as $category => $link) {
            $category = \App\Models\WaGroupLink::normalizeCategory($category);
            $link = trim($link) ?: null;

            \App\Models\WaGroupLink::updateOrCreate(
                ['ticket_category' => $category],
                ['wa_group_link' => $link]
            );
            $updated++;
        }

        return back()->with('success', "Link grup WhatsApp untuk {$updated} kategori tiket berhasil disimpan!");
    }

    // ==========================================
    // 2. CRUD KAMAR HOTEL (DI DALAM KUOTA & HARGA)
    // ==========================================

    /**
     * Simpan Tipe Kamar Baru (Multiple Photos)
     */
    public function storeHotel(Request $request): \Illuminate\Http\RedirectResponse
    {
        if ($reject = $this->rejectOversizedUploads($request, 'photos')) {
            return $reject;
        }

        $request->validate([
            'room_type'       => 'required|string|max:255',
            'price_per_night' => 'required|numeric|min:0',
            'quota'           => 'required|integer|min:0',
            'description'     => 'nullable|string',
            'photos'          => 'required|array|max:5', // Maksimal 5 foto
            'photos.*'        => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'photos.max'     => 'Maksimal 5 foto kamar yang dapat diunggah.',
            'photos.*.max'   => 'Ukuran foto kamar melebihi batas maksimal 2 MB.',
        ]);

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photoPaths[] = $photo->store('hotels', 'public');
            }
        }

        HotelRoom::create([
            'room_type'       => $request->input('room_type'),
            'price_per_night' => $request->input('price_per_night'),
            'quota'           => $request->input('quota'),
            'description'     => $request->input('description'),
            'photos'          => $photoPaths,
            'is_active'       => true,
        ]);

        return redirect()->route('admin.tickets.index')
                         ->with('success', 'Tipe kamar baru berhasil ditambahkan!');
    }

    /**
     * Update Data Kamar Hotel (Multiple Photos)
     */
    public function updateHotel(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        if ($reject = $this->rejectOversizedUploads($request, 'photos')) {
            return $reject;
        }

        $request->validate([
            'room_type'       => 'required|string|max:255',
            'price_per_night' => 'required|numeric|min:0',
            'quota'           => 'required|integer|min:0',
            'description'     => 'nullable|string',
            'photos'          => 'nullable|array|max:5',
            'photos.*'        => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'photos.max'     => 'Maksimal 5 foto kamar yang dapat diunggah.',
            'photos.*.max'   => 'Ukuran foto kamar melebihi batas maksimal 2 MB.',
        ]);

        $hotel = HotelRoom::findOrFail($id);
        
        $data = [
            'room_type'       => $request->input('room_type'),
            'price_per_night' => $request->input('price_per_night'),
            'quota'           => $request->input('quota'),
            'description'     => $request->input('description'),
        ];

        // Jika ada upload foto baru, hapus semua foto lama dan simpan yang baru
        if ($request->hasFile('photos')) {
            if (is_array($hotel->photos)) {
                foreach ($hotel->photos as $oldPhoto) {
                    if (Storage::disk('public')->exists($oldPhoto)) {
                        Storage::disk('public')->delete($oldPhoto);
                    }
                }
            }
            
            $photoPaths = [];
            foreach ($request->file('photos') as $photo) {
                $photoPaths[] = $photo->store('hotels', 'public');
            }
            $data['photos'] = $photoPaths;
        }

        $hotel->update($data);

        return redirect()->route('admin.tickets.index')
                         ->with('success', 'Data kamar hotel berhasil diperbarui!');
    }

    /**
     * Hapus Tipe Kamar Hotel (Beserta Semua Fotonya)
     */
    public function destroyHotel(int $id): \Illuminate\Http\RedirectResponse
    {
        $hotel = HotelRoom::findOrFail($id);

        if (is_array($hotel->photos)) {
            foreach ($hotel->photos as $photo) {
                if (Storage::disk('public')->exists($photo)) {
                    Storage::disk('public')->delete($photo);
                }
            }
        }

        $hotel->delete();

        return redirect()->route('admin.tickets.index')
                         ->with('success', 'Tipe kamar dan fotonya berhasil dihapus!');
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
     * HALAMAN TIKET PESERTA (2 TAB: Data Peserta [lunas] & Data All [semua status])
     */
    public function participants(Request $request)
    {
        $tab = $request->input('tab', 'peserta'); // 'peserta' = hanya lunas/fix, 'all' = semua status

        $search     = $request->input('search');
        $categories = $request->input('categories', []) ?: [];
        $wave       = $request->input('wave');
        $status     = $request->input('status');
        $dateFrom   = $request->input('date_from');
        $dateTo     = $request->input('date_to');

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

        // Filter kategori MULTIPLE (boleh pilih lebih dari satu)
        if (!empty($categories)) {
            $query->whereHas('ticket', function ($q) use ($categories) {
                $q->whereIn('ticket_category', $categories);
            });
        }

        if (!empty($wave)) {
            $query->whereHas('ticket', function ($q) use ($wave) {
                $q->where('ticket_name', 'like', "%{$wave}%");
            });
        }

        // Filter rentang tanggal pembayaran (paid_at)
        if (!empty($dateFrom)) {
            $query->whereDate('paid_at', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('paid_at', '<=', $dateTo);
        }

        if ($tab === 'peserta') {
            // Tab Data Peserta: hanya peserta yang sudah lunas / fix
            $query->where('status', 'paid');
        } elseif (!empty($status)) {
            // Tab Data All: status bisa lunas / tertunda / dibatalkan
            $query->where('status', $status);
        }

        $participants = $query->paginate(10)->withQueryString();

        // Hitung badge tiap tab
        $paidCount = \App\Models\TicketBooking::where('status', 'paid')->count();
        $allCount  = \App\Models\TicketBooking::count();

        $waves      = \App\Models\Ticket::select('ticket_name')->distinct()->pluck('ticket_name');
        $allTickets = \App\Models\Ticket::all();

        return view('admin.participants', compact(
            'participants', 'waves', 'allTickets',
            'search', 'categories', 'wave', 'status', 'tab',
            'dateFrom', 'dateTo',
            'paidCount', 'allCount'
        ));
    }

    /**
     * EXPORT EXCEL (.CSV) KOMA STANDAR & BERSIH DARI SIMBOL ANEH
     * Mengikuti tab aktif & semua filter yang sedang dipakai.
     */
    public function exportParticipants(Request $request)
    {
        $tab        = $request->input('tab', 'peserta');
        $search     = $request->input('search');
        $categories = $request->input('categories', []) ?: [];
        $wave       = $request->input('wave');
        $status     = $request->input('status');
        $dateFrom   = $request->input('date_from');
        $dateTo     = $request->input('date_to');

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

        if (!empty($categories)) {
            $query->whereHas('ticket', function ($q) use ($categories) {
                $q->whereIn('ticket_category', $categories);
            });
        }

        if (!empty($wave)) {
            $query->whereHas('ticket', function ($q) use ($wave) {
                $q->where('ticket_name', 'like', "%{$wave}%");
            });
        }

        if (!empty($dateFrom)) {
            $query->whereDate('paid_at', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('paid_at', '<=', $dateTo);
        }

        if ($tab === 'peserta') {
            $query->where('status', 'paid');
        } elseif (!empty($status)) {
            $query->where('status', $status);
        }

        $bookings = $query->get();
        $fileName = ($tab === 'all' ? 'Data-All-Peserta' : 'Data-Peserta-BACT2026') . '-' . date('Y-m-d-His') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($bookings, $tab) {
            $file = fopen('php://output', 'w');

            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'ID Pesanan',
                'Gelombang Tiket',
                'Kategori Tiket',
                'Sumber',
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
                'Catatan',
                'Tanggal Dibeli',
                'Tanggal Dibayar',
            ], ',');

            foreach ($bookings as $row) {
                $statusLabel = match ($row->status) {
                    'paid'     => 'LUNAS',
                    'pending'  => 'PENDING',
                    'cancelled'=> 'DIBATALKAN',
                    'deleted'  => 'DIHAPUS',
                    default    => strtoupper($row->status ?? ''),
                };

                fputcsv($file, [
                    $row->id,
                    $row->ticket->ticket_name ?? 'Tiket BACT',
                    $row->ticket->ticket_category ?? 'Umum',
                    $row->source === 'manual' ? 'Manual' : 'Website',
                    $row->full_name,
                    $row->name_with_title ?: $row->full_name,
                    $row->gmail_account,
                    "'" . $row->whatsapp_number,
                    "'" . $row->nik,
                    $row->amount,
                    $statusLabel,
                    $row->institution_name,
                    $row->institution_city,
                    $row->institution_province,
                    $row->profession,
                    $row->plataran_sehat_email,
                    str_replace(["\r", "\n"], ' ', $row->notes ?? ''),
                    $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '-',
                    $row->paid_at ? $row->paid_at->format('Y-m-d H:i:s') : ''
                ], ',');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * UPDATE STATUS & DATA PESERTA (AKSI EDIT DI TAB DATA ALL)
     */
    public function updateParticipantStatus(Request $request, $id)
    {
        $request->validate([
            'status'          => 'required|in:paid,pending,cancelled',
            'full_name'       => 'required|string|max:255',
            'name_with_title' => 'required|string|max:255',
            'whatsapp_number' => 'required|string|max:25',
            'ticket_id'       => 'nullable|exists:tickets,id',
            'notes'           => 'nullable|string|max:5000',
        ]);

        $booking = \App\Models\TicketBooking::findOrFail($id);
        $oldStatus = $booking->status;

        $booking->status          = $request->status;
        $booking->full_name       = $request->full_name;
        $booking->name_with_title = $request->name_with_title;
        $booking->whatsapp_number = $request->whatsapp_number;

        // Ganti tiket (kategori & gelombang) — HANYA untuk peserta manual yang BELUM dikonfirmasi.
        // Nominal pembayaran ikut menyesuaikan harga tiket baru.
        if (
            $booking->source === 'manual'
            && !$booking->confirmed_at
            && $request->filled('ticket_id')
            && (int) $request->ticket_id !== (int) $booking->ticket_id
        ) {
            $newTicket = \App\Models\Ticket::find($request->ticket_id);
            if ($newTicket) {
                $booking->ticket_id       = $newTicket->id;
                $booking->ticket_name     = $newTicket->ticket_name;
                $booking->ticket_category = $newTicket->ticket_category;
                $booking->amount          = $newTicket->price ?? $booking->amount;
            }
        }

        // Catatan & waktu perubahan
        $booking->notes = trim($request->input('notes') ?? '') ?: null;
        $booking->notes_updated_at = now();

        // Tangani kuota saat status berpindah ke/ dari 'cancelled'
        $ticket = \App\Models\Ticket::find($booking->ticket_id);
        if ($booking->status === 'cancelled' && $oldStatus !== 'cancelled' && $ticket) {
            // Kembalikan kuota hanya 1x saat pertama kali dibatalkan
            $ticket->increment('quota');
            $booking->cancelled_at = now();
        } elseif ($booking->status !== 'cancelled' && $oldStatus === 'cancelled' && $ticket) {
            // Batalkan pengembalian kuota bila diaktifkan kembali
            if ($ticket->quota > 0) {
                $ticket->decrement('quota');
            }
            $booking->cancelled_at = null;
        }

        $booking->save();

        // Invariant: peserta manual hanya boleh LUNAS setelah memiliki tanda konfirmasi.
        // (Jalur resmi: tombol Konfirmasi; jalur edit ini sekadar menjaga konsistensi.)
        if ($booking->source === 'manual' && $booking->status === 'paid' && !$booking->confirmed_at) {
            $booking->update(['confirmed_at' => now()]);
        }

        // Catat waktu pembayaran bila status diubah menjadi LUNAS (data lama dikosongkan).
        if ($booking->status === 'paid' && !$booking->paid_at) {
            $booking->update(['paid_at' => now()]);
        }

        // Kirim notifikasi WA & Email jika status diubah menjadi LUNAS
        if ($booking->status === 'paid') {
            app(TicketNotificationService::class)->sendTicketPaid($booking);
        }

        return back()->with('success', 'Data dan status peserta berhasil diperbarui!');
    }

    /**
     * HAPUS (SOFT DELETE) DATA PESERTA — berlaku dari tab Data All maupun Data Peserta.
     * Record tidak dihapus permanen: status berubah menjadi 'deleted' sehingga tetap
     * tampil di Data All (badge Dihapus) namun tidak lagi tampil di Data Peserta.
     * Alasan penghapusan wajib diisi dan dicatat ke kolom catatan.
     */
    public function destroyBooking(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $booking = TicketBooking::findOrFail($id);

        if ($booking->status === 'deleted') {
            return back()->with('error', 'Data peserta "' . ($booking->name_with_title ?: $booking->full_name) . '" sudah berstatus dihapus.');
        }

        $oldStatus = $booking->status;

        // Peserta yang sedang mengunci kuota (pending / paid) dibatalkan → kuota dikembalikan.
        // Booking yang sudah 'cancelled' sudah mengembalikan kuotanya lebih dulu → tidak diubah lagi.
        if (in_array($oldStatus, ['pending', 'paid'], true) && $booking->ticket_id) {
            Ticket::where('id', $booking->ticket_id)->increment('quota');
        }

        $noteLine = now()->format('d M Y H:i') . ' — Dihapus oleh admin: ' . trim($request->reason);
        $booking->notes = trim(($booking->notes ? $booking->notes . "\n" : '') . $noteLine);
        $booking->notes_updated_at = now();
        $booking->status = 'deleted';
        $booking->deleted_at = now();
        $booking->save();

        return back()->with('success', 'Data peserta "' . ($booking->name_with_title ?: $booking->full_name) . '" berhasil dihapus dan ditandai status Dihapus.');
    }

    /**
     * KONFIRMASI PESERTA MANUAL.
     * Hanya berlaku untuk data yang ditambahkan manual (source = 'manual') dan
     * belum dikonfirmasi. Setelah dikonfirmasi, status menjadi LUNAS sehingga
     * tercatat di tab Data Peserta — setara dengan mekanisme 'lunas' peserta website.
     */
    public function confirmParticipant(int $id): \Illuminate\Http\RedirectResponse
    {
        $booking = TicketBooking::findOrFail($id);

        if ($booking->source !== 'manual') {
            return back()->with('error', 'Konfirmasi hanya berlaku untuk peserta yang ditambahkan secara manual.');
        }

        if ($booking->confirmed_at) {
            return back()->with('error', 'Peserta "' . ($booking->name_with_title ?: $booking->full_name) . '" sudah dikonfirmasi sebelumnya.');
        }

        $oldStatus = $booking->status;
        $booking->status = 'paid';
        $booking->confirmed_at = now();
        $booking->paid_at = now();

        // Bila sebelumnya dibatalkan, tarik kembali pengembalian kuota tiket
        if ($oldStatus === 'cancelled' && $booking->ticket_id) {
            $ticket = Ticket::find($booking->ticket_id);
            if ($ticket && $ticket->quota > 0) {
                $ticket->decrement('quota');
            }
            $booking->cancelled_at = null;
        }

        $noteLine = now()->format('d M Y H:i') . ' — Konfirmasi manual oleh admin';
        $booking->notes = trim(($booking->notes ? $booking->notes . "\n" : '') . $noteLine);
        $booking->notes_updated_at = now();
        $booking->save();

        // Kirim notifikasi WA & Email (QR tiket + link grup) seperti peserta lunas
        app(TicketNotificationService::class)->sendTicketPaid($booking);

        return back()->with('success', 'Peserta "' . ($booking->name_with_title ?: $booking->full_name) . '" berhasil dikonfirmasi dan tercatat sebagai LUNAS.');
    }

    /**
     * TAMBAH PESERTA MANUAL (PESERTA TITIPAN / ADMIN ENTRY)
     * Peserta manual selalu berstatus 'pending' sampai dikonfirmasi panitia
     * melalui tombol Konfirmasi (baru tercatat LUNAS & masuk Data Peserta).
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
            'notes'           => 'nullable|string|max:5000',
        ]);

        $ticket = \App\Models\Ticket::findOrFail($request->ticket_id);

        // Hubungkan ke akun user jika email sudah terdaftar. Jika belum terdaftar,
        // user_id dibiarkan null — saat pemilik email mendaftar, tiket otomatis terhubung.
        $linkedUser = \App\Models\User::where('email', $request->gmail_account)->first();

        // Kunci kuota secara atomik — peserta manual juga mengonsumsi kuota seperti booking website.
        try {
            $booking = \Illuminate\Support\Facades\DB::transaction(function () use ($ticket, $request, $linkedUser) {
                $reserved = \App\Models\Ticket::where('id', $ticket->id)
                            ->where('quota', '>', 0)
                            ->decrement('quota');

                if ($reserved === 0) {
                    throw new \RuntimeException('kuota_habis');
                }

                return \App\Models\TicketBooking::create([
                    'user_id'              => $linkedUser ? $linkedUser->id : null,
                    'ticket_id'            => $ticket->id,
                    'ticket_name'          => $ticket->ticket_name,
                    'ticket_category'      => $ticket->ticket_category,
                    'full_name'            => $request->full_name,
                    'name_with_title'      => $request->name_with_title,
                    'gmail_account'        => $request->gmail_account,
                    'whatsapp_number'      => $request->whatsapp_number,
                    'nik'                  => $request->nik,
                    'amount'               => $ticket->price ?? 0,
                    'status'               => 'pending',
                    'source'               => 'manual',
                    'institution_name'     => $request->institution_name,
                    'institution_city'     => $request->institution_city ?? 'Kota Instansi',
                    'institution_province' => $request->institution_province ?? 'Provinsi Instansi',
                    'institution_district' => $request->institution_district ?? 'Kecamatan Instansi',
                    'profession'           => $request->profession,
                    'plataran_sehat_email' => $request->plataran_sehat_email ?? $request->gmail_account,
                    'notes'                => isset($request->notes) && trim($request->notes) !== '' ? trim($request->notes) : null,
                ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'kuota_habis') {
                return back()->with('error', 'Gagal menambahkan peserta manual: kuota tiket ini sudah habis.');
            }
            throw $e;
        }

        return back()->with('success', 'Peserta manual baru berhasil ditambahkan. Status sementara TERTUNDA — gunakan tombol Konfirmasi untuk mencatatnya sebagai peserta LUNAS (menghubungkan dengan data peserta).');
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