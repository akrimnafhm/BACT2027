<?php

use App\Models\HotelReservation;
use App\Models\HotelRoom;
use App\Models\Ticket;
use App\Models\TicketBooking;
use App\Models\User;
use App\Services\DokuApiService;
use App\Services\DokuReconciliationService;
use App\Services\DokuStatus;
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

function syncUser(): User
{
    return tap(User::create([
        'name' => 'Peserta Sync',
        'email' => 'sync-'.Str::random(6).'@test.com',
        'password' => bcrypt('password123'),
        'nik' => '3201010101'.random_int(100000, 999999),
        'gender' => 'Laki-laki',
        'phone_number' => '0812'.random_int(100000, 999999),
    ]), function (User $user) {
        $user->forceFill(['email_verified_at' => now()])->save();
    });
}

function syncTicket(int $quota = 3): Ticket
{
    return Ticket::create([
        'ticket_name' => 'Basic',
        'ticket_category' => 'Basic',
        'price' => 100000,
        'quota' => $quota,
    ]);
}

function syncBooking(User $user, Ticket $ticket, array $overrides = []): TicketBooking
{
    return TicketBooking::create(array_merge([
        'user_id' => $user->id,
        'ticket_id' => $ticket->id,
        'status' => 'pending',
        'source' => 'website',
        'ticket_name' => $ticket->ticket_name,
        'ticket_category' => $ticket->ticket_category,
        'amount' => $ticket->price,
        'invoice_number' => 'RC-SYNC-'.Str::random(6).'-1700000000',
        'payment_expired_at' => now()->subMinute(),
        'full_name' => 'Peserta Sync',
        'name_with_title' => 'Peserta Sync',
        'nik' => '3201010101010003',
        'profession' => 'Dokter',
        'whatsapp_number' => '081234567892',
        'gmail_account' => 'sync@test.com',
        'plataran_sehat_email' => 'sync@test.com',
        'institution_name' => 'RS Test',
        'institution_city' => 'Bandung',
        'institution_province' => 'Jawa Barat',
    ], $overrides));
}

function syncRoom(int $quota = 3): HotelRoom
{
    return HotelRoom::create([
        'room_type' => 'Deluxe King',
        'price_per_night' => 500000,
        'quota' => $quota,
        'is_active' => true,
    ]);
}

function syncReservation(User $user, HotelRoom $room, array $overrides = []): HotelReservation
{
    return HotelReservation::create(array_merge([
        'booking_code' => 'HTL-SYNC-'.Str::random(4),
        'user_id' => $user->id,
        'hotel_room_id' => $room->id,
        'check_in' => '2027-01-18',
        'check_out' => '2027-01-20',
        'total_nights' => 2,
        'quantity' => 2,
        'total_price' => 2000000,
        'guest_name' => 'Peserta Sync',
        'guest_email' => 'sync@test.com',
        'guest_phone' => '081234567892',
        'status' => 'pending',
        'invoice_number' => 'RC-HTL-SYNC-'.Str::random(6).'-1700000000',
        'payment_expired_at' => now()->subMinute(),
    ], $overrides));
}

function syncFakeStatus(string $invoice, ?string $transaction, ?string $order, array $extra = [], int $httpStatus = 200): void
{
    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(array_merge([
            'order' => ['invoice_number' => $invoice, 'status' => $order],
            'transaction' => ['status' => $transaction, 'date' => '2027-01-01 10:00:00'],
        ], $extra), $httpStatus),
    ]);
}

function syncPostDoku(array $payload)
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
| Tanpa channel: ORDER_EXPIRED -> batal lokal, kuota kembali tepat sekali
|--------------------------------------------------------------------------
*/

it('tanpa channel: ORDER_EXPIRED membatalkan lokal dan kuota kembali tepat sekali lintas scheduler dan webhook', function () {
    $ticket = syncTicket(quota: 3);
    $booking = syncBooking(syncUser(), $ticket);
    syncFakeStatus($booking->invoice_number, 'PENDING', 'ORDER_EXPIRED');

    $this->artisan('bookings:cancel-expired')->assertSuccessful();
    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);

    syncPostDoku([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_EXPIRED'],
        'transaction' => ['status' => 'PENDING'],
    ])->assertStatus(200)->assertJson(['status' => 'IGNORED']);

    $this->artisan('bookings:cancel-expired')->assertSuccessful();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4)
        ->and($booking->fresh()->cancelled_at)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Channel terpilih: transaction EXPIRED + ORDER_EXPIRED -> batal lokal
|--------------------------------------------------------------------------
*/

it('dengan channel terpilih: transaction EXPIRED + ORDER_EXPIRED membatalkan tiket dan kuota kembali sekali', function () {
    $ticket = syncTicket(quota: 3);
    $booking = syncBooking(syncUser(), $ticket);
    syncFakeStatus($booking->invoice_number, 'EXPIRED', 'ORDER_EXPIRED', [
        'service' => ['id' => 'VIRTUAL_ACCOUNT'],
        'channel' => ['id' => 'VIRTUAL_ACCOUNT_BSI'],
        'virtual_account_info' => ['expired_date' => '20270101120000'],
    ]);

    $this->artisan('bookings:cancel-expired')->assertSuccessful();
    $this->artisan('bookings:cancel-expired')->assertSuccessful();
    syncPostDoku([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_EXPIRED'],
        'transaction' => ['status' => 'EXPIRED'],
    ])->assertStatus(200)->assertJson(['status' => 'IGNORED']);

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);
});

it('dengan channel terpilih: reservasi hotel EXPIRED + ORDER_EXPIRED dibatalkan dan kuota kembali sekali', function () {
    $room = syncRoom(quota: 3);
    $reservation = syncReservation(syncUser(), $room);
    syncFakeStatus($reservation->invoice_number, 'EXPIRED', 'ORDER_EXPIRED', [
        'service' => ['id' => 'VIRTUAL_ACCOUNT'],
        'channel' => ['id' => 'VIRTUAL_ACCOUNT_BNI'],
    ]);

    $this->artisan('hotel-reservations:cancel-expired')->assertSuccessful();
    $this->artisan('hotel-reservations:cancel-expired')->assertSuccessful();

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($room->fresh()->quota)->toBe(5);
});

/*
|--------------------------------------------------------------------------
| Pemetaan status independen: PENDING + ORDER_EXPIRED tetap expired
|--------------------------------------------------------------------------
*/

it('Check Status API memetakan transaction dan order terpisah: PENDING + ORDER_EXPIRED = expired', function () {
    $booking = syncBooking(syncUser(), syncTicket());
    syncFakeStatus($booking->invoice_number, 'PENDING', 'ORDER_EXPIRED', [
        'service' => ['id' => 'VIRTUAL_ACCOUNT'],
        'channel' => ['id' => 'VIRTUAL_ACCOUNT_BSI'],
    ]);

    $result = app(DokuApiService::class)->checkStatus($booking->invoice_number, fresh: true);

    expect($result['transaction_status'])->toBe('PENDING')
        ->and($result['order_status'])->toBe('ORDER_EXPIRED')
        ->and($result['outcome'])->toBe(DokuStatus::EXPIRED);

    expect(app(DokuReconciliationService::class)->reconcileTicket($booking))
        ->toBe(DokuReconciliationService::ACTION_CANCELLED)
        ->and($booking->fresh()->status)->toBe('cancelled');
});

/*
|--------------------------------------------------------------------------
| Kegagalan Check Status API bukan konfirmasi expiry
|--------------------------------------------------------------------------
*/

it('kegagalan Check Status API setelah buffer tetap HELD, bukan dibatalkan', function (int $httpStatus) {
    $ticket = syncTicket(quota: 3);
    $booking = syncBooking(syncUser(), $ticket, [
        'payment_expired_at' => now()->subMinutes(5),
    ]);
    syncFakeStatus($booking->invoice_number, null, null, [], $httpStatus);

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);

    expect($action)->toBe(DokuReconciliationService::ACTION_HELD)
        ->and($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
})->with([404, 500]);

it('timeout Check Status API setelah buffer tetap HELD, bukan dibatalkan', function () {
    $ticket = syncTicket(quota: 3);
    $booking = syncBooking(syncUser(), $ticket, [
        'payment_expired_at' => now()->subMinutes(5),
    ]);

    Http::fake(function () {
        throw new ConnectionException('timeout');
    });

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);

    expect($action)->toBe(DokuReconciliationService::ACTION_HELD)
        ->and($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
});

/*
|--------------------------------------------------------------------------
| Cancel Order API tidak dipanggil untuk order yang sudah expired
|--------------------------------------------------------------------------
*/

it('pembatalan manual tidak memanggil Cancel Order API untuk order yang sudah expired', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $room = syncRoom(quota: 3);
    $reservation = syncReservation(syncUser(), $room, [
        'payment_expired_at' => now()->addHour(),
    ]);
    syncFakeStatus($reservation->invoice_number, 'EXPIRED', 'ORDER_EXPIRED', [
        'service' => ['id' => 'VIRTUAL_ACCOUNT'],
        'channel' => ['id' => 'VIRTUAL_ACCOUNT_BNI'],
    ]);

    $this->actingAs($reservation->user)
        ->post(route('hotels.cancel', $reservation->id))
        ->assertRedirect(route('hotels.index'));

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($room->fresh()->quota)->toBe(5);

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), '/checkout/v3/cancellations');
    });
});

/*
|--------------------------------------------------------------------------
| Booking paid tidak boleh turun menjadi cancelled oleh EXPIRED terlambat
|--------------------------------------------------------------------------
*/

it('booking paid tidak berubah menjadi cancelled oleh respons EXPIRED yang terlambat', function () {
    $ticket = syncTicket(quota: 3);
    $booking = syncBooking(syncUser(), $ticket, [
        'status' => 'paid',
        'paid_at' => now(),
        'payment_expired_at' => now()->subMinutes(10),
    ]);
    syncFakeStatus($booking->invoice_number, 'EXPIRED', 'ORDER_EXPIRED');

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);

    expect($action)->toBe(DokuReconciliationService::ACTION_SKIPPED)
        ->and($booking->fresh()->status)->toBe('paid')
        ->and($booking->fresh()->paid_at)->not->toBeNull()
        ->and($ticket->fresh()->quota)->toBe(3);

    Http::assertNothingSent();
});

it('webhook EXPIRED untuk booking paid tidak menurunkan status dan tidak mengubah kuota', function () {
    $ticket = syncTicket(quota: 3);
    $booking = syncBooking(syncUser(), $ticket, [
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    syncPostDoku([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_EXPIRED'],
        'transaction' => ['status' => 'EXPIRED'],
    ])->assertStatus(200)->assertJson(['status' => 'IGNORED']);

    expect($booking->fresh()->status)->toBe('paid')
        ->and($ticket->fresh()->quota)->toBe(3);
});
