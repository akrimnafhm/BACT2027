<?php

use App\Models\Ticket;
use App\Models\TicketBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.doku.client_id' => 'TEST-CLIENT-ID',
        'services.doku.secret_key' => 'TEST-SECRET-KEY',
        'services.doku.is_production' => false,
        'services.doku.notification_url' => 'http://localhost/api/doku/notification',
        'services.doku.check_status_cache_seconds' => 0,
        'services.doku.payment_due_date_minutes' => 1440,
        'services.doku.local_expiry_fallback_hours' => 24,
        'services.doku.hold_grace_minutes' => 15,
        'services.doku.lapse_buffer_seconds' => 90,
        'services.doku.cancel_order_enabled' => false,
    ]);
});

function uxUser(): User
{
    return tap(User::create([
        'name' => 'Peserta UX',
        'email' => 'ux-'.Str::random(6).'@test.com',
        'password' => bcrypt('password123'),
        'nik' => '3201010101'.random_int(100000, 999999),
        'gender' => 'Laki-laki',
        'phone_number' => '0812'.random_int(100000, 999999),
    ]), function (User $user) {
        $user->forceFill(['email_verified_at' => now()])->save();
    });
}

function uxTicket(int $quota = 3): Ticket
{
    return Ticket::create([
        'ticket_name' => 'Basic',
        'ticket_category' => 'Basic',
        'price' => 100000,
        'quota' => $quota,
    ]);
}

function uxBooking(User $user, Ticket $ticket, array $overrides = []): TicketBooking
{
    return TicketBooking::create(array_merge([
        'user_id' => $user->id,
        'ticket_id' => $ticket->id,
        'status' => 'pending',
        'source' => 'website',
        'ticket_name' => $ticket->ticket_name,
        'ticket_category' => $ticket->ticket_category,
        'amount' => $ticket->price,
        'invoice_number' => 'RC-UX-'.Str::random(6).'-1700000000',
        'payment_expired_at' => now()->subMinute(),
        'full_name' => 'Peserta UX',
        'name_with_title' => 'Peserta UX',
        'nik' => '3201010101010003',
        'profession' => 'Dokter',
        'whatsapp_number' => '081234567892',
        'gmail_account' => 'ux@test.com',
        'plataran_sehat_email' => 'ux@test.com',
        'institution_name' => 'RS Test',
        'institution_city' => 'Bandung',
        'institution_province' => 'Jawa Barat',
    ], $overrides));
}

function uxFakeStatus(string $invoice, ?string $transaction, ?string $order, array $extra = [], int $httpStatus = 200): void
{
    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(array_merge([
            'order' => ['invoice_number' => $invoice, 'status' => $order],
            'transaction' => ['status' => $transaction, 'date' => '2027-01-01 10:00:00'],
        ], $extra), $httpStatus),
    ]);
}

function uxSelectedChannel(): array
{
    return [
        'service' => ['id' => 'VIRTUAL_ACCOUNT'],
        'channel' => ['id' => 'VIRTUAL_ACCOUNT_BSI'],
    ];
}

/*
|--------------------------------------------------------------------------
| Setelah deadline: jangan tawarkan pembayaran lagi
|--------------------------------------------------------------------------
*/

it('setelah deadline menampilkan status pemeriksaan dan menyembunyikan tombol lanjutkan pembayaran', function () {
    $booking = uxBooking(uxUser(), uxTicket());
    uxFakeStatus($booking->invoice_number, 'PENDING', 'ORDER_GENERATED', uxSelectedChannel());

    $this->actingAs($booking->user)
        ->get(route('booking.index'))
        ->assertOk()
        ->assertSee('Waktu Pembayaran Berakhir')
        ->assertSee('Kami sedang memeriksa status pembayaran ke DOKU')
        ->assertSee('Perbarui Status')
        ->assertDontSee('Lanjutkan Pembayaran')
        ->assertDontSee('Pesanan Menunggu Pembayaran');

    expect($booking->fresh()->status)->toBe('pending');
});

it('sebelum deadline tetap menampilkan tombol lanjutkan pembayaran', function () {
    Http::fake();

    $booking = uxBooking(uxUser(), uxTicket(), [
        'payment_expired_at' => now()->addMinutes(10),
    ]);

    $this->actingAs($booking->user)
        ->get(route('booking.index'))
        ->assertOk()
        ->assertSee('Pesanan Menunggu Pembayaran')
        ->assertSee('Lanjutkan Pembayaran')
        ->assertDontSee('Waktu Pembayaran Berakhir');

    expect($booking->fresh()->status)->toBe('pending');
});

it('membuka checkout setelah deadline diarahkan ke status pemeriksaan tanpa membuat pembayaran baru', function () {
    $booking = uxBooking(uxUser(), uxTicket());
    uxFakeStatus($booking->invoice_number, 'PENDING', 'ORDER_GENERATED', uxSelectedChannel());

    $this->actingAs($booking->user)
        ->get(route('checkout', $booking->id))
        ->assertRedirect(route('booking.index'))
        ->assertSessionHas('error', fn ($message) => str_contains($message, 'Waktu pembayaran telah berakhir'));

    expect($booking->fresh()->status)->toBe('pending');

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), '/checkout/v1/payment');
    });
});

/*
|--------------------------------------------------------------------------
| Rekonsiliasi sukses: expired -> cancelled, kuota kembali sekali
|--------------------------------------------------------------------------
*/

it('rekonsiliasi sukses EXPIRED membatalkan booking dan kuota kembali tepat sekali', function () {
    $ticket = uxTicket(quota: 3);
    $booking = uxBooking(uxUser(), $ticket);
    uxFakeStatus($booking->invoice_number, 'EXPIRED', 'ORDER_EXPIRED', uxSelectedChannel());

    $this->actingAs($booking->user)
        ->get(route('booking.index'))
        ->assertOk()
        ->assertSee('Pemesanan Dibatalkan')
        ->assertDontSee('Lanjutkan Pembayaran')
        ->assertDontSee('Waktu Pembayaran Berakhir');

    $this->actingAs($booking->user)
        ->get(route('booking.index'))
        ->assertOk();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($booking->fresh()->cancelled_at)->not->toBeNull()
        ->and($ticket->fresh()->quota)->toBe(4);
});

/*
|--------------------------------------------------------------------------
| Rekonsiliasi gagal: tetap pending, tetapi tetap tidak boleh dibayar
|--------------------------------------------------------------------------
*/

it('kegagalan Check Status API tidak dianggap expiry dan tetap menampilkan status pemeriksaan', function (int $httpStatus) {
    $ticket = uxTicket(quota: 3);
    $booking = uxBooking(uxUser(), $ticket, [
        'payment_expired_at' => now()->subMinutes(5),
    ]);
    uxFakeStatus($booking->invoice_number, null, null, [], $httpStatus);

    $this->actingAs($booking->user)
        ->get(route('booking.index'))
        ->assertOk()
        ->assertSee('Waktu Pembayaran Berakhir')
        ->assertSee('Kami sedang memeriksa status pembayaran ke DOKU')
        ->assertDontSee('Lanjutkan Pembayaran');

    expect($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
})->with([404, 500]);

it('timeout Check Status API tidak dianggap expiry dan tetap menampilkan status pemeriksaan', function () {
    $ticket = uxTicket(quota: 3);
    $booking = uxBooking(uxUser(), $ticket, [
        'payment_expired_at' => now()->subMinutes(5),
    ]);

    Http::fake(function () {
        throw new ConnectionException('timeout');
    });

    $this->actingAs($booking->user)
        ->get(route('booking.index'))
        ->assertOk()
        ->assertSee('Waktu Pembayaran Berakhir')
        ->assertDontSee('Lanjutkan Pembayaran');

    expect($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
});

/*
|--------------------------------------------------------------------------
| Paid tidak boleh diturunkan oleh respons EXPIRED yang terlambat
|--------------------------------------------------------------------------
*/

it('booking paid tetap paid dan tidak menampilkan status pemeriksaan walau timer sudah lewat', function () {
    Http::fake();

    $ticket = uxTicket(quota: 3);
    $booking = uxBooking(uxUser(), $ticket, [
        'status' => 'paid',
        'paid_at' => now(),
        'payment_expired_at' => now()->subMinutes(10),
    ]);

    $this->actingAs($booking->user)
        ->get(route('booking.index'))
        ->assertOk()
        ->assertDontSee('Waktu Pembayaran Berakhir')
        ->assertDontSee('Lanjutkan Pembayaran');

    expect($booking->fresh()->status)->toBe('paid')
        ->and($booking->fresh()->paid_at)->not->toBeNull()
        ->and($ticket->fresh()->quota)->toBe(3);

    Http::assertNothingSent();
});
