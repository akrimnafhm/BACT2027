<?php

namespace App\Http\Controllers;

use App\Models\NotificationTemplate;
use App\Models\WaGroupLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\FonnteService;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    /**
     * Tampilkan Halaman Form Broadcast WA di Admin Panel
     */
    public function index()
    {
        $paidBookings = DB::table('ticket_bookings')
            ->where('status', 'paid')
            ->whereNotNull('whatsapp_number')
            ->where('whatsapp_number', '!=', '')
            ->select('id', 'whatsapp_number')
            ->get();

        // Satu nomor = satu pesan (tiket digabung), jadi hitung nomor unik.
        $totalParticipants = $paidBookings
            ->map(fn ($b) => self::canonicalPhone($b->whatsapp_number))
            ->unique()
            ->count();

        // Preload isi textarea dengan template notifikasi Ticket WA agar
        // admin tinggal edit seperlunya sebelum kirim (tetap bisa diubah bebas).
        $templateBody = NotificationTemplate::where('key', 'ticket_paid_wa')->value('body') ?? '';

        return view('admin.broadcast', compact('totalParticipants', 'templateBody'));
    }

    /**
     * Proses Kirim Pesan Broadcast Terpersonalisasi
     */
    public function send(Request $request, FonnteService $fonnteService)
    {
        $request->validate([
            'target_type'    => 'required|in:all,manual',
            'manual_numbers' => 'required_if:target_type,manual|nullable|string',
            'message'        => 'required|string|min:5',
            'delay'          => 'required|integer|min:2|max:30',
        ], [
            'manual_numbers.required_if' => 'Nomor WhatsApp manual wajib diisi jika memilih opsi Input Manual.',
            'message.required'           => 'Isi pesan broadcast wajib diisi.',
            'delay.min'                  => 'Jeda waktu (delay) minimal 2 detik agar nomor tidak terdeteksi spam.',
        ]);

        $message = $request->input('message');
        $delay   = $request->input('delay', 3);
        $recipients = [];

        // 1. Tentukan target penerima & namanya
        if ($request->input('target_type') === 'all') {
            
            // PERBAIKAN: Hanya ambil peserta dari ticket_bookings yang STATUS-NYA SUDAH PAID
            $bookings = DB::table('ticket_bookings')
                          ->where('status', 'paid') // <-- FILTER RESMI HANYA YANG SUDAH BAYAR
                          ->whereNotNull('whatsapp_number')
                          ->where('whatsapp_number', '!=', '')
                          ->select(
                              'id',
                              'name_with_title',
                              'whatsapp_number',
                              'ticket_name',
                              'ticket_category',
                              'invoice_number',
                              'amount',
                              'gmail_account',
                              'checkin_token'
                          )
                          ->get();

            if ($bookings->isEmpty()) {
                return back()->withErrors(['error' => 'Belum ada peserta dengan status pembayaran LUNAS (Paid) di database.']);
            }

            // Satu nomor bisa punya banyak tiket lunas -> gabung jadi satu pesan
            // agar penerima tidak dispam banyak pesan sekaligus.
            $grouped = $bookings->groupBy(fn ($b) => self::canonicalPhone($b->whatsapp_number));

            foreach ($grouped as $group) {
                $recipients[] = $this->buildRecipient($group->first()->whatsapp_number, $group->all());
            }

        } else {
            // Opsi Input Manual (Untuk Testing)
            $rawNumbers = preg_split('/[\r\n,]+/', $request->input('manual_numbers'));
            $cleanNumbers = array_filter(array_map('trim', $rawNumbers));

            if (empty($cleanNumbers)) {
                return back()->withErrors(['error' => 'Nomor WhatsApp manual tidak valid.']);
            }

            // Cocokkan tiap nomor manual ke booking LUNAS agar placeholder ikut terisi.
            // Satu nomor bisa punya banyak tiket lunas -> gabung jadi satu pesan.
            $bookingsByPhone = DB::table('ticket_bookings')
                ->where('status', 'paid')
                ->whereNotNull('whatsapp_number')
                ->where('whatsapp_number', '!=', '')
                ->select(
                    'id',
                    'name_with_title',
                    'whatsapp_number',
                    'ticket_name',
                    'ticket_category',
                    'invoice_number',
                    'amount',
                    'gmail_account',
                    'checkin_token'
                )
                ->get()
                ->groupBy(fn ($b) => self::canonicalPhone($b->whatsapp_number));

            // Nomor manual duplikat (mis. 0812... dan 62812...) digabung jadi satu pesan.
            $uniqueNumbers = collect($cleanNumbers)
                ->groupBy(fn ($num) => self::canonicalPhone($num));

            foreach ($uniqueNumbers as $group) {
                $matches = $bookingsByPhone->get(self::canonicalPhone($group->first())) ?? collect();

                $recipients[] = $this->buildRecipient($group->first(), $matches->all());
            }
        }

        // 2. Kirim pesan terpersonalisasi ke Fonnte
        $result = $fonnteService->sendBulkPersonalized($recipients, $message, $delay);

        if ($result && isset($result['status']) && $result['status'] == true) {
            $count = count($recipients);
            return back()->with('success', "Pesan broadcast terpersonalisasi berhasil masuk antrean kirim ke {$count} nomor WhatsApp (satu nomor satu pesan, tiket digabung)!");
        }

        return back()->withErrors(['error' => 'Gagal mengirim pesan ke Fonnte. Periksa token API di file .env Anda.']);
    }

    /**
     * Kanonikalisasi nomor (08xx / 628xx / +62...) agar format berbeda
     * dari nomor yang sama dianggap satu penerima.
     */
    protected static function canonicalPhone($phone): string
    {
        $digits = ltrim(preg_replace('/[^0-9]/', '', (string) $phone), '0');
        if (str_starts_with($digits, '62')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Susun satu penerima broadcast dari SEMUA tiket lunas milik satu nomor.
     * Placeholder tiket digabung agar satu nomor hanya menerima satu pesan:
     * - {tiket}: 1 tiket ditulis menyamping tanpa nomor
     *   ("Early Bird - Basic (ID: 8, Kode: BACT-8-...)"), >1 tiket jadi
     *   daftar bernomor multi-baris ("1. ...\n2. ...")
     * - {id_pesanan}, {kode_tiket}, {invoice} jadi daftar dipisah koma
     * - {harga} jadi total gabungan, {jumlah_tiket} = banyaknya tiket lunas
     * - {email} dari booking terbaru; {link_grup} satu baris per kategori unik
     *   ("Link Grup Basic: ..."); data kosong (nomor tak dikenal) diisi '-'.
     */
    protected function buildRecipient($phone, array $bookings): array
    {
        if (empty($bookings)) {
            return [
                'name'         => 'Peserta BACT',
                'phone'        => $phone,
                'jumlah_tiket' => '-',
                'tiket'        => '-',
                'id_pesanan'   => '-',
                'kode_tiket'   => '-',
                'invoice'      => '-',
                'harga'        => '-',
                'email'        => '-',
                'link_grup'    => '-',
            ];
        }

        $bookings = collect($bookings)->sortBy('id')->values();
        $latest = $bookings->last();

        // Satu tiket: tulis menyamping tanpa nomor agar pas di kalimat
        // "tiket *{tiket}* Anda". Banyak tiket: daftar bernomor multi-baris
        // agar angka "1." jatuh di awal baris, bukan di samping kalimat.
        $describe = function ($b) {
            $ticketName = $b->ticket_name ?? null;
            $ticketCategory = $b->ticket_category ?? null;
            $ticket = ($ticketName ? $ticketName . ' - ' : '') . ($ticketCategory ?: '-');

            return $ticket . ' (ID: ' . $b->id . ', Kode: ' . ($b->checkin_token ?: '-') . ')';
        };

        if ($bookings->count() === 1) {
            $tiketText = $describe($bookings->first());
        } else {
            $tiketText = $bookings->values()
                ->map(fn ($b, $i) => ($i + 1) . '. ' . $describe($b))
                ->implode("\n");
        }

        // Satu baris berlabel per kategori unik agar tiap link grup tercantum.
        $linkLines = [];
        foreach ($bookings as $b) {
            $category = $b->ticket_category ? WaGroupLink::normalizeCategory($b->ticket_category) : null;
            if (!$category || isset($linkLines[$category])) {
                continue;
            }
            $link = WaGroupLink::linkFor($b->ticket_category);
            if ($link) {
                $linkLines[$category] = 'Link Grup ' . $category . ': ' . $link;
            }
        }

        return [
            'name'         => $latest->name_with_title ?: 'Peserta BACT',
            'phone'        => $phone,
            'jumlah_tiket' => (string) $bookings->count(),
            'tiket'        => $tiketText,
            'id_pesanan'   => $bookings->map(fn ($b) => (string) $b->id)->implode(', '),
            'kode_tiket'   => $bookings->map(fn ($b) => $b->checkin_token ?: '-')->implode(', '),
            'invoice'      => $bookings->map(fn ($b) => $b->invoice_number ?: '-')->implode(', '),
            'harga'        => number_format((float) $bookings->sum(fn ($b) => (float) ($b->amount ?? 0)), 0, ',', '.'),
            'email'        => $latest->gmail_account ?: '-',
            'link_grup'    => !empty($linkLines) ? implode("\n", $linkLines) : '-',
        ];
    }
}
