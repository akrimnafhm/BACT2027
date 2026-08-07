<?php

namespace App\Http\Controllers;

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
        // PERBAIKAN: Hitung hanya tiket yang SUDAH PAID dan punya nomor WhatsApp
        $totalParticipants = DB::table('ticket_bookings')
                               ->where('status', 'paid') // <-- SESUAIKAN KATA 'paid' DENGAN STATUS DI DB KAMU
                               ->whereNotNull('whatsapp_number')
                               ->where('whatsapp_number', '!=', '')
                               ->count();

        return view('admin.broadcast', compact('totalParticipants'));
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
                          ->select('name_with_title as name', 'whatsapp_number as phone')
                          ->get();

            if ($bookings->isEmpty()) {
                return back()->withErrors(['error' => 'Belum ada peserta dengan status pembayaran LUNAS (Paid) di database.']);
            }

            foreach ($bookings as $b) {
                $recipients[] = [
                    'name'  => $b->name ?: 'Peserta BACT',
                    'phone' => $b->phone,
                ];
            }

        } else {
            // Opsi Input Manual (Untuk Testing)
            $rawNumbers = preg_split('/[\r\n,]+/', $request->input('manual_numbers'));
            $cleanNumbers = array_filter(array_map('trim', $rawNumbers));

            if (empty($cleanNumbers)) {
                return back()->withErrors(['error' => 'Nomor WhatsApp manual tidak valid.']);
            }

            foreach ($cleanNumbers as $num) {
                $recipients[] = [
                    'name'  => 'Peserta BACT', // Default nama jika input manual
                    'phone' => $num,
                ];
            }
        }

        // 2. Kirim pesan terpersonalisasi ke Fonnte
        $result = $fonnteService->sendBulkPersonalized($recipients, $message, $delay);

        if ($result && isset($result['status']) && $result['status'] == true) {
            $count = count($recipients);
            return back()->with('success', "Pesan broadcast terpersonalisasi berhasil masuk antrean kirim ke {$count} peserta resmi!");
        }

        return back()->withErrors(['error' => 'Gagal mengirim pesan ke Fonnte. Periksa token API di file .env Anda.']);
    }
}