<?php

use App\Models\HotelReservation;
use App\Models\HotelRoom;
use App\Models\Ticket;
use App\Models\TicketBooking;
use App\Models\User;
use App\Services\DokuExpiry;
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
    ]);
});

function holdUser(): User
{
    return tap(User::create([
        'name' => 'Peserta Hold',
        'email' => 'hold-'.Str::random(6).'@test.com',
        'password' => bcrypt('password123'),
        'nik' => '3201010101'.random_int(100000, 999999),
        'gender' => 'Laki-laki',
        'phone_number' => '0812'.random_int(100000, 999999),
    ]), function (User $user) {
        $user->forceFill(['email_verified_at' => now()])->save();
    });
}

function holdTicket(int $quota = 5): Ticket
{
    return Ticket::create([
        'ticket_name' => 'Basic',
        'ticket_category' => 'Basic',
        'price' => 100000,
        'quota' => $quota,
    ]);
}

function holdBooking(User $user, Ticket $ticket, array $overrides = []): TicketBooking
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
        'payment_expired_at' => now()->subMinute(),
        'full_name' => 'Peserta Hold',
        'name_with_title' => 'Peserta Hold',
        'nik' => '3201010101010003',
        'profession' => 'Dokter',
        'whatsapp_number' => '081234567892',
        'gmail_account' => 'hold@test.com',
        'plataran_sehat_email' => 'hold@test.com',
        'institution_name' => 'RS Test',
        'institution_city' => 'Bandung',
        'institution_province' => 'Jawa Barat',
    ], $overrides));
}

function holdRoom(int $quota = 3): HotelRoom
{
    return HotelRoom::create([
        'room_type' => 'Deluxe King',
        'price_per_night' => 500000,
        'quota' => $quota,
        'is_active' => true,
    ]);
}

function holdReservation(User $user, HotelRoom $room, array $overrides = []): HotelReservation
{
    return HotelReservation::create(array_merge([
        'booking_code' => 'HTL-HOLD-'.Str::random(4),
        'user_id' => $user->id,
        'hotel_room_id' => $room->id,
        'check_in' => '2027-01-18',
        'check_out' => '2027-01-20',
        'total_nights' => 2,
        'quantity' => 2,
        'total_price' => 2000000,
        'guest_name' => 'Peserta Hold',
        'guest_email' => 'hold@test.com',
        'guest_phone' => '081234567892',
        'status' => 'pending',
        'invoice_number' => 'RC-HTL-141-1700000000',
        'payment_expired_at' => now()->subMinute(),
    ], $overrides));
}

function holdFakeStatus(?string $txn, ?string $order, array $extra = [], int $http = 200): void
{
    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(array_merge([
            'order' => ['invoice_number' => 'INV', 'status' => $order],
            'transaction' => ['status' => $txn, 'date' => '2027-01-01 10:00:00'],
        ], $extra), $http),
    ]);
}

function holdPostDoku(array $payload)
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
| Skenario 141: PENDING + ORDER_GENERATED lewat due date (tanpa channel)
|--------------------------------------------------------------------------
*/

it('skenario 141: PENDING + GENERATED lewat due date tetap HOLD walau scheduler berulang', function () {
    $ticket = holdTicket(quota: 3);
    $booking = holdBooking(holdUser(), $ticket);
    holdFakeStatus('PENDING', 'ORDER_GENERATED');

    $this->artisan('bookings:cancel-expired')->assertSuccessful();
    $this->artisan('bookings:cancel-expired')->assertSuccessful();

    expect($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
});

/*
|--------------------------------------------------------------------------
| Skenario 142: transaction EXPIRED dari VA (order masih GENERATED)
|--------------------------------------------------------------------------
*/

it('skenario 142: transaction EXPIRED dari VA membatalkan walau order masih GENERATED', function () {
    $ticket = holdTicket(quota: 3);
    $booking = holdBooking(holdUser(), $ticket);
    holdFakeStatus('EXPIRED', 'ORDER_GENERATED', [
        'service' => ['id' => 'VIRTUAL_ACCOUNT'],
        'channel' => ['id' => 'VIRTUAL_ACCOUNT_BANK_SYARIAH_MANDIRI'],
        'virtual_account_info' => ['expired_date' => '20270101120000'],
    ]);

    $this->artisan('bookings:cancel-expired')->assertSuccessful();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);
});

it('skenario 143: PENDING + ORDER_EXPIRED membatalkan (checkout kedaluwarsa)', function () {
    $ticket = holdTicket(quota: 3);
    $booking = holdBooking(holdUser(), $ticket);
    holdFakeStatus('PENDING', 'ORDER_EXPIRED');

    $this->artisan('bookings:cancel-expired')->assertSuccessful();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);
});

/*
|--------------------------------------------------------------------------
| HOLD berbatas waktu (grace)
|--------------------------------------------------------------------------
*/

it('heldBeyondGrace false sebelum tenggang, true sesudahnya, false tanpa expiry DOKU', function () {
    $expiry = app(DokuExpiry::class);
    $user = holdUser();
    $ticket = holdTicket();

    $within = holdBooking($user, $ticket, ['payment_expired_at' => now()->subMinutes(5)]);
    $beyond = holdBooking($user, $ticket, [
        'invoice_number' => 'RC-BACT-142-1700000000',
        'payment_expired_at' => now()->subMinutes(20),
    ]);
    $noExpiry = holdBooking($user, $ticket, [
        'invoice_number' => 'RC-BACT-143-1700000000',
        'payment_expired_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect($expiry->heldBeyondGrace($within))->toBeFalse()
        ->and($expiry->heldBeyondGrace($beyond))->toBeTrue()
        ->and($expiry->heldBeyondGrace($noExpiry))->toBeFalse();
});

it('checkout menampilkan pesan waktu pembayaran berakhir selama masa tenggang', function () {
    $user = holdUser();
    $booking = holdBooking($user, holdTicket(), ['payment_expired_at' => now()->subMinutes(5)]);
    // Dengan channel terpilih, aturan lapsed tidak berlaku -> tetap HOLD + pesan verifikasi.
    holdFakeStatus('PENDING', 'ORDER_GENERATED', [
        'service' => ['id' => 'VIRTUAL_ACCOUNT'],
        'channel' => ['id' => 'VIRTUAL_ACCOUNT_BSI'],
    ]);

    $this->actingAs($user)
        ->get(route('checkout', $booking->id))
        ->assertRedirect(route('booking.index'))
        ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'Waktu pembayaran telah berakhir'));

    expect($booking->fresh()->status)->toBe('pending');
});

it('checkout menampilkan pesan eksplisit + arahan batalkan setelah masa tenggang', function () {
    $user = holdUser();
    $booking = holdBooking($user, holdTicket(), ['payment_expired_at' => now()->subMinutes(20)]);
    // Dengan channel terpilih, aturan lapsed tidak berlaku -> HOLD + pesan grace
    // (tanpa channel, booking ini sudah dibatalkan via cancelled_lapsed).
    holdFakeStatus('PENDING', 'ORDER_GENERATED', [
        'service' => ['id' => 'VIRTUAL_ACCOUNT'],
        'channel' => ['id' => 'VIRTUAL_ACCOUNT_BSI'],
    ]);

    $this->actingAs($user)
        ->get(route('checkout', $booking->id))
        ->assertRedirect(route('booking.index'))
        ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'membatalkan'));

    // BUKAN auto-cancel: status lokal tetap pending menunggu keputusan DokuStatus/user.
    expect($booking->fresh()->status)->toBe('pending');
});

it('hotel melewati tenggang juga mendapat pesan eksplisit (bukan auto-cancel)', function () {
    $user = holdUser();
    $room = holdRoom();
    $reservation = holdReservation($user, $room, ['payment_expired_at' => now()->subMinutes(20)]);
    // Dengan channel terpilih, aturan lapsed tidak berlaku -> HOLD + pesan grace.
    holdFakeStatus('PENDING', 'ORDER_GENERATED', [
        'service' => ['id' => 'VIRTUAL_ACCOUNT'],
        'channel' => ['id' => 'VIRTUAL_ACCOUNT_BNI'],
    ]);

    $this->actingAs($user)
        ->get(route('hotels.checkout', $reservation->id))
        ->assertRedirect(route('hotels.index'))
        ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'membatalkan'));

    expect($reservation->fresh()->status)->toBe('pending');
});

/*
|--------------------------------------------------------------------------
| Pembayaran terlambat (SUCCESS setelah cancelled / notifikasi untuk paid)
|--------------------------------------------------------------------------
*/

it('webhook SUCCESS untuk reservasi hotel cancelled: HELD, status & kuota tidak berubah', function () {
    $room = holdRoom(quota: 4);
    $reservation = holdReservation(holdUser(), $room, [
        'status' => 'cancelled',
        'cancelled_at' => now(),
    ]);

    $response = holdPostDoku([
        'order' => ['invoice_number' => $reservation->invoice_number, 'status' => 'ORDER_EXPIRED'],
        'transaction' => ['status' => 'SUCCESS'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'HELD']);
    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($room->fresh()->quota)->toBe(4);
});

it('webhook EXPIRED untuk reservasi hotel paid: IGNORED, paid tidak turun', function () {
    $room = holdRoom(quota: 3);
    $reservation = holdReservation(holdUser(), $room, ['status' => 'paid']);

    $response = holdPostDoku([
        'order' => ['invoice_number' => $reservation->invoice_number, 'status' => 'ORDER_EXPIRED'],
        'transaction' => ['status' => 'EXPIRED'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'IGNORED']);
    expect($reservation->fresh()->status)->toBe('paid')
        ->and($room->fresh()->quota)->toBe(3);
});

it('webhook non-final untuk tiket paid: IGNORED, paid tidak turun', function () {
    $ticket = holdTicket(quota: 3);
    $booking = holdBooking(holdUser(), $ticket, ['status' => 'paid', 'paid_at' => now()]);

    $response = holdPostDoku([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'PENDING'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'IGNORED']);
    expect($booking->fresh()->status)->toBe('paid')
        ->and($ticket->fresh()->quota)->toBe(3);
});
