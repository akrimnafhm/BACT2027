<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\TicketBooking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CheckinDemoSeeder extends Seeder
{
    /**
     * Menambah 50 data dummy peserta yang sudah CHECK-IN ke dalam ticket_bookings.
     * Data ini realistis untuk simulasi / demo halaman QR check-in.
     */
    public function run(): void
    {
        // Ambil tiket aktif yang tersedia beserta kategorinya.
        // Pakai tiket yang masih punya kuota agar data tidak mengganggu stok asli secara fatal.
        $tickets = Ticket::where('is_active', true)->where('quota', '>', 0)->get();

        if ($tickets->isEmpty()) {
            $this->command->error('Tidak ada tiket aktif yang tersedia untuk membuat data dummy check-in.');
            return;
        }

        $now = now();

        // Bank nama orang Indonesia untuk generate data yang beragam.
        $firstNames = ['Budi', 'Siti', 'Ahmad', 'Rina', 'Dedi', 'Putri', 'Agus', 'Dewi', 'Rizal', 'Fitri',
            'Hendra', 'Lina', 'Joko', 'Sari', 'Bambang', 'Maya', 'Eko', 'Ratna', 'Yudi', 'Nina',
            'Fajar', 'Wulan', 'Rudi', 'Ani', 'Tono', 'Desi', 'Hadi', 'Intan', 'Reza', 'Mega',
            'Andi', 'Sri', 'Galih', 'Vina', 'Rian', 'Citra', 'Bayu', 'Sinta', 'Ilham', 'Nadia',
            'Dimas', 'Ayu', 'Rahmat', 'Indah', 'Fikri', 'Lia', 'Mahesa', 'Tika', 'Yoga', 'Rani'];
        $lastNames = ['Santoso', 'Rahayu', 'Hidayat', 'Wijaya', 'Saputra', 'Ningsih', 'Pratama', 'Anggraini',
            'Kurniawan', 'Wulandari', 'Setiawan', 'Lestari', 'Gunawan', 'Utami', 'Suryadi', 'Pertiwi',
            'Nugroho', 'Handayani', 'Ramadhan', 'Kusuma', 'Maulana', 'Astuti', 'Cahyono', 'Sumarni',
            'Purnomo', 'Melati', 'Susanto', 'Apriyani', 'Hartono', 'Permata'];
        $professions = ['Dokter Umum', 'Dokter Spesialis', 'Perawat', 'Bidan', 'Apoteker', 'Dokter Gigi', 'Analis Kesehatan'];
        $cities = ['Jakarta', 'Surabaya', 'Bandung', 'Semarang', 'Yogyakarta', 'Medan', 'Makassar', 'Palembang', 'Denpasar', 'Balikpapan'];
        $provinces = ['DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'DI Yogyakarta', 'Jawa Timur', 'Sumatera Utara', 'Sulawesi Selatan', 'Bali', 'Kalimantan Timur'];
        $institutions = ['RSUD Dr. Soetomo', 'RS Cipto Mangunkusumo', 'RS Hasan Sadikin', 'RS Dr. Sardjito', 'RS Dr. Kariadi', 'Puskesmas Kota', 'Klinik Sehat', 'RSUD Kota'];

        for ($i = 0; $i < 50; $i++) {
            // Pilih tiket secara acak (distribusi di semua kategori).
            $ticket = $tickets->random();

            $firstName = $firstNames[$i % count($firstNames)];
            $lastName  = $lastNames[($i * 7) % count($lastNames)];
            $fullName  = $firstName . ' ' . $lastName;
            $nik       = '35' . str_pad((string) mt_rand(0, 99999999999999), 14, '0', STR_PAD_LEFT);

            $paidAt = $now->copy()->subDays(mt_rand(1, 30))->subHours(mt_rand(0, 12));

            $booking = TicketBooking::create([
                'user_id'              => null,
                'ticket_id'            => $ticket->id,
                'ticket_name'          => $ticket->ticket_name,
                'ticket_category'      => $ticket->ticket_category,
                'amount'               => $ticket->price ?? 0,
                'status'               => 'paid',
                'source'               => 'website',
                'full_name'            => $fullName,
                'name_with_title'      => $fullName . ', ' . $professions[$i % count($professions)],
                'nik'                  => $nik,
                'profession'           => $professions[$i % count($professions)],
                'whatsapp_number'      => '08' . mt_rand(1000000000, 9999999999),
                'gmail_account'        => strtolower($firstName . $lastName) . ($i + 1) . '@gmail.com',
                'plataran_sehat_email' => strtolower($firstName . $lastName) . ($i + 1) . '@gmail.com',
                'institution_name'     => $institutions[$i % count($institutions)],
                'institution_province' => $provinces[$i % count($provinces)],
                'institution_city'     => $cities[$i % count($cities)],
                'institution_district' => 'Kec. ' . $cities[$i % count($cities)],
                'invoice_number'       => 'RC-BACT-' . mt_rand(1000, 99999) . '-' . time() . $i,
                'paid_at'              => $paidAt,
                'confirmed_at'         => $paidAt->copy()->addMinutes(mt_rand(5, 120)),
                'notified_at'          => $paidAt->copy()->addMinutes(mt_rand(1, 30)),
            ]);

            // Set token check-in & tanggal check-in (waktu bervariasi saat event berlangsung).
            $booking->update([
                'checkin_token' => 'BACT-' . $booking->id . '-' . strtoupper(Str::random(10)),
                'checked_in_at' => $paidAt->copy()->addDays(mt_rand(5, 25))->addHours(mt_rand(7, 17)),
            ]);

            // Kurangi kuota secara nyata (agar konsisten seperti alur normal).
            if ($ticket->quota > 0) {
                $ticket->decrement('quota');
            }
        }

        $this->command->info('Berhasil menambahkan 50 data dummy peserta check-in.');
    }
}
