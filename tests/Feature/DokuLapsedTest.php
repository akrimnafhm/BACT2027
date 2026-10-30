<?php

use App\Models\HotelReservation;
use App\Models\HotelRoom;
use App\Models\Ticket;
use App\Models\TicketBooking;
use App\Models\User;
use App\Services\DokuReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
});

function lapsedUser(): User
{
    return tap(User::create([
        'name' => 'Peserta Lapsed',
        'email' => 'lapsed-'.Str::random(6).'@test.com',
        'password' => bcrypt('password123'),
        'nik' => '3201010101'.random_int(100000, 999999),
        'gender' => 'Laki-laki',
        'phone_number' => '0812'.random_int(100000, 999999),
    ]), function (User $user) {
        $user->forceFill(['email_verified_at' => now()])->save();
    });
}

function lapsedTicket(int $quota = 5): Ticket
{
    return Ticket::create([
        'ticket_name' => 'Basic',
        'ticket_category' => 'Basic',
        'price' => 100000,
        'quota' => $quota,
    ]);
}

function lapsedBooking(User $user, Ticket $ticket, array $overrides = []): TicketBooking
{
    return TicketBooking::create(array_merge([
        'user_id' => $user->id,
        'ticket_id' => $ticket->id,
        'status' => 'pending',
        'source' => 'website',
        'ticket_name' => $ticket->ticket_name,
        'ticket_category' => $ticket->ticket_category,
        'amount' => $ticket->price,
        'invoice_number' => 'RC-BACT-141-1700000000',
        'payment_expired_at' => now()->subMinutes(5),
        'full_name' => 'Peserta Lapsed',
        'name_with_title' => 'Peserta Lapsed',
        'nik' => '3201010101010003',
        'profession' => 'Dokter',
        'whatsapp_number' => '081234567892',
        'gmail_account' => 'lapsed@test.com',
        'plataran_sehat_email' => 'lapsed@test.com',
        'institution_name' => 'RS Test',
        'institution_city' => 'Bandung',
        'institution_province' => 'Jawa Barat',
    ], $overrides));
}

function lapsedRoom(int $quota = 3): HotelRoom
{
    return HotelRoom::create([
        'room_type' => 'Deluxe King',
        'price_per_night' => 500000,
        'quota' => $quota,
        'is_active' => true,
    ]);
}

function lapsedReservation(User $user, HotelRoom $room, array $overrides = []): HotelReservation
{
    return HotelReservation::create(array_merge([
        'booking_code' => 'HTL-LAP-'.Str::random(4),
        'user_id' => $user->id,
        'hotel_room_id' => $room->id,
        'check_in' => '2027-01-18',
        'check_out' => '2027-01-20',
        'total_nights' => 2,
        'quantity' => 2,
        'total_price' => 2000000,
        'guest_name' => 'Peserta Lapsed',
        'guest_email' => 'lapsed@test.com',
        'guest_phone' => '081234567892',
        'status' => 'pending',
        'invoice_number' => 'RC-HTL-141-1700000000',
        'payment_expired_at' => now()->subMinutes(5),
    ], $overrides));
}

function lapsedFakeStatus(?string $txn, ?string $order, array $extra = []): void
{
    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(array_merge([
            'order' => ['invoice_number' => 'INV', 'status' => $order],
            'transaction' => ['status' => $txn, 'date' => '2027-01-01 10:00:00'],
        ], $extra), 200),
    ]);
}

function lapsedPostDoku(array $payload)
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

/*
|--------------------------------------------------------------------------
| Tiket: checkout_lapsed_no_channel
|--------------------------------------------------------------------------
*/

it('tiket lapsed tanpa channel dibatalkan dengan action cancelled_lapsed', function () {
    $ticket = lapsedTicket(quota: 3);
    $booking = lapsedBooking(lapsedUser(), $ticket);
    lapsedFakeStatus('PENDING', 'ORDER_GENERATED');

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);

    expect($action)->toBe(DokuReconciliationService::ACTION_CANCELLED_LAPSED)
        ->and($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);
});

it('tiket lapsed via scheduler ikut dibatalkan dan dihitung', function () {
    $ticket = lapsedTicket(quota: 3);
    $booking = lapsedBooking(lapsedUser(), $ticket);
    lapsedFakeStatus('PENDING', 'ORDER_GENERATED');

    $this->artisan('bookings:cancel-expired')->assertSuccessful();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);
});

it('belum lewat buffer: tetap HELD (tidak dibatalkan)', function () {
    $ticket = lapsedTicket(quota: 3);
    $booking = lapsedBooking(lapsedUser(), $ticket, ['payment_expired_at' => now()->subSeconds(30)]);
    lapsedFakeStatus('PENDING', 'ORDER_GENERATED');

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);

    expect($action)->toBe(DokuReconciliationService::ACTION_HELD)
        ->and($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
});

it('channel terpilih + HOLD: tetap HELD walau lewat buffer', function () {
    $ticket = lapsedTicket(quota: 3);
    $booking = lapsedBooking(lapsedUser(), $ticket);
    lapsedFakeStatus('PENDING', 'ORDER_GENERATED', [
        'service' => ['id' => 'VIRTUAL_ACCOUNT'],
        'channel' => ['id' => 'VIRTUAL_ACCOUNT_BSI'],
    ]);

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);

    expect($action)->toBe(DokuReconciliationService::ACTION_HELD)
        ->and($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
});

it('PAID tetap menang atas aturan lapsed', function () {
    $ticket = lapsedTicket(quota: 3);
    $booking = lapsedBooking(lapsedUser(), $ticket);
    lapsedFakeStatus('SUCCESS', 'ORDER_GENERATED');

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);

    expect($action)->toBe(DokuReconciliationService::ACTION_PAID)
        ->and($booking->fresh()->status)->toBe('paid')
        ->and($ticket->fresh()->quota)->toBe(3);
});

it('klik lanjutkan pembayaran pada booking lapsed: pesan kedaluwarsa, bukan verifikasi', function () {
    $user = lapsedUser();
    $booking = lapsedBooking($user, lapsedTicket());
    lapsedFakeStatus('PENDING', 'ORDER_GENERATED');

    $this->actingAs($user)
        ->get(route('checkout', $booking->id))
        ->assertRedirect(route('booking.index'))
        ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'otomatis dibatalkan'));

    expect($booking->fresh()->status)->toBe('cancelled');
});

it('SUCCESS setelah cancelled_lapsed tidak diabaikan diam-diam (HELD + catatan)', function () {
    $ticket = lapsedTicket(quota: 4);
    $booking = lapsedBooking(lapsedUser(), $ticket);
    lapsedFakeStatus('PENDING', 'ORDER_GENERATED');
    app(DokuReconciliationService::class)->reconcileTicket($booking);
    expect($booking->fresh()->status)->toBe('cancelled');

    $response = lapsedPostDoku([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'SUCCESS'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'HELD']);
    $fresh = $booking->fresh();
    expect($fresh->status)->toBe('cancelled')
        ->and((string) $fresh->notes)->toContain('PERLU TINDAK LANJUT')
        ->and($ticket->fresh()->quota)->toBe(5);
});

/*
|--------------------------------------------------------------------------
| Hotel: checkout_lapsed_no_channel
|--------------------------------------------------------------------------
*/

it('hotel lapsed tanpa channel dibatalkan dengan action cancelled_lapsed', function () {
    $room = lapsedRoom(quota: 3);
    $reservation = lapsedReservation(lapsedUser(), $room);
    lapsedFakeStatus('PENDING', 'ORDER_GENERATED');

    $action = app(DokuReconciliationService::class)->reconcileHotel($reservation);

    expect($action)->toBe(DokuReconciliationService::ACTION_CANCELLED_LAPSED)
        ->and($reservation->fresh()->status)->toBe('cancelled')
        ->and($room->fresh()->quota)->toBe(5);
});

it('hotel belum lewat buffer: tetap HELD', function () {
    $room = lapsedRoom(quota: 3);
    $reservation = lapsedReservation(lapsedUser(), $room, ['payment_expired_at' => now()->subSeconds(30)]);
    lapsedFakeStatus('PENDING', 'ORDER_GENERATED');

    $action = app(DokuReconciliationService::class)->reconcileHotel($reservation);

    expect($action)->toBe(DokuReconciliationService::ACTION_HELD)
        ->and($reservation->fresh()->status)->toBe('pending')
        ->and($room->fresh()->quota)->toBe(3);
});

it('hotel dengan channel terpilih + HOLD: tetap HELD walau lewat buffer', function () {
    $room = lapsedRoom(quota: 3);
    $reservation = lapsedReservation(lapsedUser(), $room);
    lapsedFakeStatus('PENDING', 'ORDER_GENERATED', [
        'service' => ['id' => 'VIRTUAL_ACCOUNT'],
        'channel' => ['id' => 'VIRTUAL_ACCOUNT_BNI'],
    ]);

    $action = app(DokuReconciliationService::class)->reconcileHotel($reservation);

    expect($action)->toBe(DokuReconciliationService::ACTION_HELD)
        ->and($reservation->fresh()->status)->toBe('pending')
        ->and($room->fresh()->quota)->toBe(3);
});

it('SUCCESS setelah hotel cancelled_lapsed: HELD, status & kuota tidak berubah', function () {
    $room = lapsedRoom(quota: 4);
    $reservation = lapsedReservation(lapsedUser(), $room);
    lapsedFakeStatus('PENDING', 'ORDER_GENERATED');
    app(DokuReconciliationService::class)->reconcileHotel($reservation);
    expect($reservation->fresh()->status)->toBe('cancelled');

    $response = lapsedPostDoku([
        'order' => ['invoice_number' => $reservation->invoice_number, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'SUCCESS'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'HELD']);
    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($room->fresh()->quota)->toBe(6);
});
