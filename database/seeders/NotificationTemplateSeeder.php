<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key'         => 'ticket_paid_wa',
                'channel'     => 'wa',
                'label'       => 'Notifikasi Tiket Lunas (WhatsApp)',
                'subject'     => null,
                'include_qr'  => true,
                'is_active'   => true,
                'body'        => 'Halo {nama},

Selamat! Pembayaran tiket *{tiket}* Anda telah kami terima.

Berikut detail pemesanan Anda:
- Tiket: {tiket}
- ID Pesanan: {id_pesanan}
- Kode Tiket: {kode_tiket}
- Nomor Invoice: {invoice}
- Total: Rp {harga}

{qr}

Simpan QR tiket ini untuk proses check-in pada hari-H.

Salam hangat,
Panitia BACT 2027',
            ],
            [
                'key'         => 'ticket_paid_email',
                'channel'     => 'email',
                'label'       => 'Notifikasi Tiket Lunas (Email)',
                'subject'     => 'Konfirmasi Pembelian Tiket - BACT 2027',
                'include_qr'  => true,
                'is_active'   => true,
                'body'        => '<p>Halo <strong>{nama}</strong>,</p>
<p>Selamat! Pembayaran tiket <strong>{tiket}</strong> Anda telah kami terima.</p>
<p>Berikut detail pemesanan Anda:</p>
<ul>
    <li><strong>ID Pesanan:</strong> {id_pesanan}</li><li><strong>Kode Tiket:</strong> {kode_tiket}</li>
    <li><strong>Nomor Invoice:</strong> {invoice}</li>
    <li><strong>Total:</strong> Rp {harga}</li>
</ul>
<p>QR tiket Anda:</p>
{qr}
<p>Simpan QR ini untuk proses check-in pada hari-H. Sampai jumpa di simposium BACT 2027!</p>
<p>Salam hangat,<br>Panitia BACT 2027</p>',
            ],
            [
                'key'         => 'hotel_paid_wa',
                'channel'     => 'whatsapp',
                'label'       => 'Notifikasi Hotel Lunas (WhatsApp)',
                'subject'     => null,
                'include_qr'  => false,
                'is_active'   => true,
                'body'        => 'Halo {nama},

Selamat! Pembayaran reservasi hotel *{hotel}* Anda telah kami terima.

Berikut ringkasan pemesanan Anda:
- Kode Booking: {kode_booking}
- Check-in: {check_in}
- Check-out: {check_out}
- Jumlah Malam: {malam}
- Total: Rp {harga}

Terima kasih,
Panitia BACT 2027',
            ],
            [
                'key'         => 'hotel_paid_email',
                'channel'     => 'email',
                'label'       => 'Notifikasi Hotel Lunas (Email)',
                'subject'     => 'Konfirmasi Reservasi Hotel - BACT 2027',
                'include_qr'  => false,
                'is_active'   => true,
                'body'        => '<p>Halo <strong>{nama}</strong>,</p><p>Selamat! Pembayaran reservasi hotel <strong>{hotel}</strong> Anda telah kami terima.</p><p>Berikut ringkasan pemesanan Anda:</p><ul><li><strong>Kode Booking:</strong> {kode_booking}</li><li><strong>Check-in:</strong> {check_in}</li><li><strong>Check-out:</strong> {check_out}</li><li><strong>Jumlah Malam:</strong> {malam}</li><li><strong>Total:</strong> Rp {harga}</li></ul><p>Terima kasih telah memesan hotel untuk BACT 2027. Sampai jumpa!</p><p>Salam hangat,<br>Panitia BACT 2027</p>',
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}
