<?php

use App\Models\HotelReservation;
use App\Models\HotelRoom;
use App\Models\Ticket;
use App\Models\TicketBooking;
use App\Models\User;
use App\Services\BookingCancellationService;
use App\Services\DokuApiService;
use App\Services\DokuReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
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
        'services.doku.cancel_order_enabled' => false,
        'services.doku.hold_grace_minutes' => 15,
        'services.doku.lapse_buffer_seconds' => 90,
    ]);

    Cache::flush();
});

function rxUser(): User
{
    return tap(User::create([
        'name' => 'Peserta Recovered',
        'email' => 'rx-'.Str::random(6).'@test.com',
        'password' => bcrypt('password123'),
        'nik' => '3201010101'.random_int(100000, 999999),
        'gender' => 'Laki-laki',
        'phone_number' => '0812'.random_int(100000, 999999),
    ]), function (User $user) {
        $user->forceFill(['email_verified_at' => now()])->save();
    });
}

function rxTicket(int $quota = 5): Ticket
{
    return Ticket::create([
        'ticket_name' => 'Basic',
        'ticket_category' => 'Basic',
        'price' => 100000,
        'quota' => $quota,
    ]);
}

function rxBooking(User $user, Ticket $ticket, array $overrides = []): TicketBooking
{
    return TicketBooking::create(array_merge([
        'user_id' => $user->id,
        'ticket_id' => $ticket->id,
        'status' => 'pending',
        'source' => 'website',
        'ticket_name' => $ticket->ticket_name,
        'ticket_category' => $ticket->ticket_category,
        'amount' => $ticket->price,
        'invoice_number' => 'RC-RX-'.Str::random(10),
        'payment_expired_at' => now()->subMinutes(5),
        'full_name' => 'Peserta Recovered',
        'name_with_title' => 'Peserta Recovered',
        'nik' => '3201010101010003',
        'profession' => 'Dokter',
        'whatsapp_number' => '081234567892',
        'gmail_account' => 'rx@test.com',
        'plataran_sehat_email' => 'rx@test.com',
        'institution_name' => 'RS Test',
        'institution_city' => 'Bandung',
        'institution_province' => 'Jawa Barat',
    ], $overrides));
}

function rxRoom(int $quota = 5): HotelRoom
{
    return HotelRoom::create([
        'room_type' => 'Deluxe King',
        'price_per_night' => 500000,
        'quota' => $quota,
        'is_active' => true,
    ]);
}

function rxReservation(User $user, HotelRoom $room, array $overrides = []): HotelReservation
{
    return HotelReservation::create(array_merge([
        'booking_code' => 'HTL-RX-'.Str::random(4),
        'user_id' => $user->id,
        'hotel_room_id' => $room->id,
        'check_in' => '2027-01-18',
        'check_out' => '2027-01-20',
        'total_nights' => 2,
        'quantity' => 2,
        'total_price' => 2000000,
        'guest_name' => 'Peserta Recovered',
        'guest_email' => 'rx@test.com',
        'guest_phone' => '081234567892',
        'status' => 'pending',
        'invoice_number' => 'RC-HTL-RX-'.Str::random(10),
        'payment_expired_at' => now()->subMinutes(5),
    ], $overrides));
}

function rxBody(?string $transaction, ?string $order, array $extra = []): array
{
    return array_merge([
        'order' => ['invoice_number' => 'INV', 'status' => $order],
        'transaction' => ['status' => $transaction, 'date' => '2027-01-01 10:00:00'],
    ], $extra);
}

function rxPostDoku(array $payload)
{
    $body = json_encode($payload);
    $clientId = config('services.doku.client_id');
    $requestId = (string) Str::uuid();
    $timestamp = gmdate('Y-m-d\TH:i:s\Z');
    $target = '/api/doku/notification';
    $digest = base64_encode(hash('sha256', $body, true));
    $raw = 'Client-Id:'.$clientId."\n".'Request-Id:'.$requestId."\n".'Request-Timestamp:'.$timestamp."\n".'Request-Target:'.$target."\n".'Digest:'.$digest;
    $signature = 'HMACSHA256='.base64_encode(hash_hmac('sha256', $raw, config('services.doku.secret_key'), true));

    return test()->call('POST', $target, [], [], [], [
        'HTTP_CLIENT_ID' => $clientId,
        'HTTP_REQUEST_ID' => $requestId,
        'HTTP_REQUEST_TIMESTAMP' => $timestamp,
        'HTTP_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], $body);
}

/**
 * Fake Check Status yang mengembalikan urutan respons berbeda per panggilan.
 *
 * @param  array<int, array<string, mixed>|Throwable>  $responses
 */
function rxFakeSequence(array $responses): void
{
    $call = 0;

    Http::fake(function () use (&$call, $responses) {
        $response = $responses[$call] ?? end($responses);
        $call++;

        if ($response instanceof Throwable) {
            throw $response;
        }

        return Http::response($response, 200);
    });
}

/*
|--------------------------------------------------------------------------
| Tahap 1: ORDER_RECOVERED tidak boleh dibatalkan jalur lapse
|--------------------------------------------------------------------------
*/

it('tiket ORDER_RECOVERED tetap pending walau PENDING tanpa channel dan lewat buffer', function () {
    $ticket = rxTicket(quota: 3);
    $booking = rxBooking(rxUser(), $ticket);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(rxBody('PENDING', 'ORDER_RECOVERED'), 200),
    ]);

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);

    expect($action)->toBe(DokuReconciliationService::ACTION_HELD)
        ->and($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
});

it('hotel ORDER_RECOVERED tetap pending walau PENDING tanpa channel dan lewat buffer', function () {
    $room = rxRoom(quota: 3);
    $reservation = rxReservation(rxUser(), $room);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(rxBody('PENDING', 'ORDER_RECOVERED'), 200),
    ]);

    $action = app(DokuReconciliationService::class)->reconcileHotel($reservation);

    expect($action)->toBe(DokuReconciliationService::ACTION_HELD)
        ->and($reservation->fresh()->status)->toBe('pending')
        ->and($room->fresh()->quota)->toBe(3);
});

/*
|--------------------------------------------------------------------------
| Regresi: keputusan lama tetap berjalan
|--------------------------------------------------------------------------
*/

it('ORDER_EXPIRED tetap membatalkan booking tiket dan mengembalikan kuota', function () {
    config(['services.doku.check_status_cache_seconds' => 120]);

    $ticket = rxTicket(quota: 3);
    $booking = rxBooking(rxUser(), $ticket);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(rxBody('PENDING', 'ORDER_EXPIRED'), 200),
    ]);

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);

    expect($action)->toBe(DokuReconciliationService::ACTION_CANCELLED)
        ->and($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);

    Http::assertSentCount(2);
});

it('transaksi SUCCESS tetap menghasilkan PAID tanpa fresh check tambahan', function () {
    config(['services.doku.check_status_cache_seconds' => 120]);

    $ticket = rxTicket(quota: 3);
    $booking = rxBooking(rxUser(), $ticket);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(rxBody('SUCCESS', 'ORDER_GENERATED'), 200),
    ]);

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);

    expect($action)->toBe(DokuReconciliationService::ACTION_PAID)
        ->and($booking->fresh()->status)->toBe('paid')
        ->and($ticket->fresh()->quota)->toBe(3);

    Http::assertSentCount(1);
});

/*
|--------------------------------------------------------------------------
| Tahap 2: fresh check sebelum keputusan destruktif
|--------------------------------------------------------------------------
*/

it('fresh check dipakai sebelum cancel dan ORDER_RECOVERED dari fresh menahan pembatalan', function () {
    config(['services.doku.check_status_cache_seconds' => 120]);

    $ticket = rxTicket(quota: 3);
    $booking = rxBooking(rxUser(), $ticket);

    rxFakeSequence([
        rxBody('PENDING', 'ORDER_EXPIRED'),
        rxBody('PENDING', 'ORDER_RECOVERED'),
    ]);

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);

    expect($action)->toBe(DokuReconciliationService::ACTION_HELD)
        ->and($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);

    Http::assertSentCount(2);
});

it('fresh check timeout tidak menyebabkan pembatalan', function () {
    config(['services.doku.check_status_cache_seconds' => 120]);

    $ticket = rxTicket(quota: 3);
    $booking = rxBooking(rxUser(), $ticket);

    rxFakeSequence([
        rxBody('PENDING', 'ORDER_EXPIRED'),
        new ConnectionException('timeout'),
    ]);

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);

    expect($action)->toBe(DokuReconciliationService::ACTION_HELD)
        ->and($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
});

it('fresh response 200 tanpa status dikenali tidak menyebabkan pembatalan', function () {
    config(['services.doku.check_status_cache_seconds' => 120]);

    $ticket = rxTicket(quota: 3);
    $booking = rxBooking(rxUser(), $ticket);

    rxFakeSequence([
        rxBody('PENDING', 'ORDER_EXPIRED'),
        [],
    ]);

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);

    expect($action)->toBe(DokuReconciliationService::ACTION_HELD)
        ->and($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
});

it('fresh check 5xx tidak menyebabkan pembatalan', function () {
    config(['services.doku.check_status_cache_seconds' => 120]);

    $ticket = rxTicket(quota: 3);
    $booking = rxBooking(rxUser(), $ticket);

    $call = 0;
    Http::fake(function () use (&$call) {
        $call++;

        if ($call === 1) {
            return Http::response(rxBody('PENDING', 'ORDER_EXPIRED'), 200);
        }

        return Http::response(['message' => 'Server Error'], 500);
    });

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);

    expect($action)->toBe(DokuReconciliationService::ACTION_HELD)
        ->and($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
});

it('fresh check 404 tidak menyebabkan pembatalan otomatis', function () {
    config(['services.doku.check_status_cache_seconds' => 120]);

    $ticket = rxTicket(quota: 3);
    $booking = rxBooking(rxUser(), $ticket);

    $call = 0;
    Http::fake(function () use (&$call) {
        $call++;

        if ($call === 1) {
            return Http::response(rxBody('PENDING', 'ORDER_EXPIRED'), 200);
        }

        return Http::response(['message' => 'Not Found'], 404);
    });

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);

    expect($action)->toBe(DokuReconciliationService::ACTION_HELD)
        ->and($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);

    Http::assertSentCount(2);
});

/*
|--------------------------------------------------------------------------
| Tahap 2: invalidasi cache dari webhook (key sama)
|--------------------------------------------------------------------------
*/

it('webhook PAID menginvalidasi cache invoice terkait', function () {
    config(['services.doku.check_status_cache_seconds' => 120]);

    $booking = rxBooking(rxUser(), rxTicket());
    $invoice = $booking->invoice_number;

    rxFakeSequence([
        rxBody('PENDING', 'ORDER_GENERATED'),
        rxBody('SUCCESS', 'ORDER_GENERATED'),
    ]);

    $first = app(DokuApiService::class)->checkStatus($invoice);
    expect($first['outcome'])->toBe('hold');

    rxPostDoku([
        'order' => ['invoice_number' => $invoice, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'SUCCESS'],
    ])->assertStatus(200);

    $second = app(DokuApiService::class)->checkStatus($invoice);

    expect($second['outcome'])->toBe('paid')
        ->and($second['cached'])->toBeFalse()
        ->and($booking->fresh()->status)->toBe('paid');

    Http::assertSentCount(2);
});

it('webhook ORDER_RECOVERED menginvalidasi cache invoice terkait', function () {
    config(['services.doku.check_status_cache_seconds' => 120]);

    $booking = rxBooking(rxUser(), rxTicket());
    $invoice = $booking->invoice_number;

    rxFakeSequence([
        rxBody('PENDING', 'ORDER_GENERATED'),
        rxBody('PENDING', 'ORDER_RECOVERED'),
    ]);

    app(DokuApiService::class)->checkStatus($invoice);

    rxPostDoku([
        'order' => ['invoice_number' => $invoice, 'status' => 'ORDER_RECOVERED'],
        'transaction' => ['status' => 'PENDING'],
    ])->assertStatus(200);

    $second = app(DokuApiService::class)->checkStatus($invoice);

    expect($second['order_status'])->toBe('ORDER_RECOVERED')
        ->and($second['cached'])->toBeFalse();

    Http::assertSentCount(2);
});

/*
|--------------------------------------------------------------------------
| Tahap 2/4: scheduler, race, dan idempotensi
|--------------------------------------------------------------------------
*/

it('scheduler tidak membatalkan booking memakai hasil cache lama', function () {
    config(['services.doku.check_status_cache_seconds' => 120]);

    $ticket = rxTicket(quota: 3);
    $booking = rxBooking(rxUser(), $ticket);

    rxFakeSequence([
        rxBody('PENDING', 'ORDER_EXPIRED'),
        rxBody('PENDING', 'ORDER_RECOVERED'),
    ]);

    $this->artisan('bookings:cancel-expired')->assertSuccessful();

    expect($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);

    Http::assertSentCount(2);
});

it('webhook PAID menang atas scheduler: status tetap paid dan kuota tidak kembali dua kali', function () {
    $ticket = rxTicket(quota: 3);
    $ticket->decrement('quota');

    $booking = rxBooking(rxUser(), $ticket);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(rxBody('PENDING', 'ORDER_EXPIRED'), 200),
    ]);

    rxPostDoku([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'SUCCESS'],
    ])->assertStatus(200);

    expect($booking->fresh()->status)->toBe('paid');

    $this->artisan('bookings:cancel-expired')->assertSuccessful();

    $cancelled = app(BookingCancellationService::class)->cancelTicket($booking->fresh(), 'uji race');

    expect($cancelled)->toBeFalse()
        ->and($booking->fresh()->status)->toBe('paid')
        ->and($ticket->fresh()->quota)->toBe(2);
});

it('pembatalan lokal tiket idempoten dan kuota hanya kembali sekali', function () {
    $ticket = rxTicket(quota: 3);
    $ticket->decrement('quota');

    $booking = rxBooking(rxUser(), $ticket);
    $canceller = app(BookingCancellationService::class);

    expect($canceller->cancelTicket($booking, 'uji idempotensi'))->toBeTrue()
        ->and($canceller->cancelTicket($booking->fresh(), 'uji idempotensi'))->toBeFalse()
        ->and($ticket->fresh()->quota)->toBe(3);
});

it('pembatalan lokal hotel idempoten dan kuota hanya kembali sekali', function () {
    $room = rxRoom(quota: 5);
    $room->decrement('quota', 2);

    $reservation = rxReservation(rxUser(), $room, ['quantity' => 2]);
    $canceller = app(BookingCancellationService::class);

    expect($canceller->cancelHotel($reservation, 'uji idempotensi'))->toBeTrue()
        ->and($canceller->cancelHotel($reservation->fresh(), 'uji idempotensi'))->toBeFalse()
        ->and($room->fresh()->quota)->toBe(5);
});

/*
|--------------------------------------------------------------------------
| Tahap 3: 404 konservatif pada jalur resolve (ticket & hotel)
|--------------------------------------------------------------------------
*/

it('resolve tiket dengan 404 menahan booking tanpa mengembalikan kuota', function () {
    $user = rxUser();
    $ticket = rxTicket(quota: 3);
    $ticket->decrement('quota');

    $booking = rxBooking($user, $ticket, [
        'invoice_number' => 'RC-PCR-'.Str::random(8),
        'payment_url' => null,
        'payment_expired_at' => null,
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(['message' => 'Not Found'], 404),
    ]);

    $this->actingAs($user)
        ->get(route('booking.form'))
        ->assertRedirect(route('booking.index'))
        ->assertSessionHas('error', fn ($message) => str_contains($message, 'masih memiliki pesanan tiket'));

    expect($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(2);
});

it('resolve hotel dengan 404 menahan reservasi tanpa mengembalikan kuota', function () {
    $user = rxUser();
    $room = rxRoom(quota: 5);
    $room->decrement('quota', 2);

    $reservation = rxReservation($user, $room, [
        'invoice_number' => 'RC-HTL-RX-'.Str::random(8),
        'payment_url' => null,
        'payment_expired_at' => null,
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(['message' => 'Not Found'], 404),
    ]);

    $this->actingAs($user)
        ->post(route('hotels.store', $room->id), [
            'check_in' => '2027-01-18',
            'check_out' => '2027-01-20',
            'quantity' => 1,
        ])
        ->assertRedirect(route('hotels.index'))
        ->assertSessionHas('error', fn ($message) => str_contains($message, 'menunggu pembayaran'));

    expect($reservation->fresh()->status)->toBe('pending')
        ->and($room->fresh()->quota)->toBe(3)
        ->and(HotelReservation::where('user_id', $user->id)->count())->toBe(1);
});
