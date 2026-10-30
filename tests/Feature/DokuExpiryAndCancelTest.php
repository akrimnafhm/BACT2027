<?php

use App\Models\HotelReservation;
use App\Models\HotelRoom;
use App\Models\Ticket;
use App\Models\TicketBooking;
use App\Models\User;
use App\Services\BookingCancellationService;
use App\Services\DokuCancelService;
use App\Services\DokuExpiry;
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
        'services.doku.cancel_order_enabled' => false,
    ]);
});

function expiryUser(string $role = 'peserta'): User
{
    $suffix = (string) random_int(100000, 999999);

    return tap(User::create([
        'name' => 'Peserta Expiry',
        'email' => 'expiry-'.Str::random(6).'@test.com',
        'password' => bcrypt('password123'),
        'nik' => '3201010101'.$suffix,
        'gender' => 'Laki-laki',
        'phone_number' => '0812'.$suffix,
    ]), function (User $user) use ($role) {
        $user->forceFill(['email_verified_at' => now(), 'role' => $role])->save();
    });
}

function expiryTicket(int $quota = 5): Ticket
{
    return Ticket::create([
        'ticket_name' => 'Basic',
        'ticket_category' => 'Basic',
        'price' => 100000,
        'quota' => $quota,
    ]);
}

function expiryTicketBooking(User $user, Ticket $ticket, array $overrides = []): TicketBooking
{
    return TicketBooking::create(array_merge([
        'user_id' => $user->id,
        'ticket_id' => $ticket->id,
        'status' => 'pending',
        'source' => 'website',
        'ticket_name' => $ticket->ticket_name,
        'ticket_category' => $ticket->ticket_category,
        'amount' => $ticket->price,
        'invoice_number' => null,
        'payment_expired_at' => null,
        'full_name' => 'Peserta Expiry',
        'name_with_title' => 'Peserta Expiry, S.Ked',
        'nik' => '3201010101010003',
        'profession' => 'Dokter',
        'whatsapp_number' => '081234567892',
        'gmail_account' => 'expiry@test.com',
        'plataran_sehat_email' => 'expiry@test.com',
        'institution_name' => 'RS Test',
        'institution_city' => 'Bandung',
        'institution_province' => 'Jawa Barat',
    ], $overrides));
}

function expiryRoom(int $quota = 5): HotelRoom
{
    return HotelRoom::create([
        'room_type' => 'Deluxe King',
        'price_per_night' => 500000,
        'quota' => $quota,
        'is_active' => true,
    ]);
}

function expiryReservation(User $user, HotelRoom $room, array $overrides = []): HotelReservation
{
    return HotelReservation::create(array_merge([
        'booking_code' => 'HTL-EXP-'.Str::random(4),
        'user_id' => $user->id,
        'hotel_room_id' => $room->id,
        'check_in' => '2027-01-18',
        'check_out' => '2027-01-20',
        'total_nights' => 2,
        'quantity' => 2,
        'total_price' => 2000000,
        'guest_name' => 'Peserta Expiry',
        'guest_email' => 'expiry@test.com',
        'guest_phone' => '081234567892',
        'status' => 'pending',
        'invoice_number' => null,
        'payment_expired_at' => null,
    ], $overrides));
}

function fakeCreateOrder(array $payment): void
{
    Http::fake([
        'api-sandbox.doku.com/checkout/v1/payment' => Http::response([
            'response' => ['payment' => $payment],
        ], 200),
    ]);
}

function expiryCheckStatus(string $invoice, ?string $transactionStatus, ?string $orderStatus, ?string $originalRequestId = null, int $httpStatus = 200, array $extra = []): array
{
    return array_replace_recursive([
        'order' => ['invoice_number' => $invoice, 'status' => $orderStatus],
        'transaction' => [
            'status' => $transactionStatus,
            'date' => '2027-01-01 10:00:00',
            'original_request_id' => $originalRequestId,
        ],
    ], $extra);
}

/*
|--------------------------------------------------------------------------
| P1: Parser expiry DOKU (regresi expired_datetime)
|--------------------------------------------------------------------------
*/

it('DokuExpiry memparse expired_datetime format yyyyMMddHHmmss (UTC+7)', function () {
    $parsed = app(DokuExpiry::class)->parse([
        'response' => ['payment' => ['expired_datetime' => '20300101123045']],
    ]);

    expect($parsed)->not->toBeNull()
        ->and($parsed->copy()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'))
        ->toBe('2030-01-01 12:30:45');
});

it('DokuExpiry memparse expired_date dan expiredDate sebagai alternatif', function () {
    $fromDate = app(DokuExpiry::class)->parse([
        'response' => ['payment' => ['expired_date' => '20300202090000']],
    ]);
    $fromCamel = app(DokuExpiry::class)->parse([
        'response' => ['payment' => ['expiredDate' => '2030-02-02T09:00:00+07:00']],
    ]);

    expect($fromDate)->not->toBeNull()
        ->and($fromDate->copy()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i'))->toBe('2030-02-02 09:00')
        ->and($fromCamel)->not->toBeNull()
        ->and($fromCamel->copy()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i'))->toBe('2030-02-02 09:00');
});

it('DokuExpiry mengembalikan null bila DOKU tidak menyertakan expiry (tidak mengarang fallback)', function () {
    expect(app(DokuExpiry::class)->parse(['response' => ['payment' => []]]))->toBeNull()
        ->and(app(DokuExpiry::class)->parse([]))->toBeNull()
        ->and(app(DokuExpiry::class)->parse(['response' => ['payment' => ['expired_datetime' => 'bukan-tanggal']]]))->toBeNull();
});

it('DokuExpiry.effectiveExpiry memakai fallback lokal dari created_at bila payment_expired_at null', function () {
    $room = expiryRoom();
    $reservation = expiryReservation(expiryUser(), $room, [
        'created_at' => now()->subHours(2),
        'updated_at' => now()->subHours(2),
    ]);

    $expiry = app(DokuExpiry::class)->effectiveExpiry($reservation);

    expect($expiry)->not->toBeNull()
        ->and($expiry->copy()->setTimezone(config('app.timezone'))->format('Y-m-d H:i'))
        ->toBe($reservation->created_at->copy()->addHours(24)->setTimezone(config('app.timezone'))->format('Y-m-d H:i'))
        ->and(app(DokuExpiry::class)->isExpired($reservation))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| P1: payment_due_date tiket & hotel (menit)
|--------------------------------------------------------------------------
*/

it('mengirim payment_due_date 1440 menit pada create-order hotel', function () {
    fakeCreateOrder([
        'url' => 'https://sandbox.doku.com/checkout/abc',
        'expired_datetime' => '20300101123045',
    ]);

    $room = expiryRoom();
    $reservation = expiryReservation(expiryUser(), $room);

    $this->actingAs($reservation->user)
        ->get(route('hotels.checkout', $reservation->id))
        ->assertOk();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/checkout/v1/payment')
            && str_contains($request->body(), '"payment_due_date":1440');
    });

    $stored = $reservation->fresh()->payment_expired_at;
    expect($stored)->not->toBeNull()
        ->and($stored->copy()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'))->toBe('2030-01-01 12:30:45');
});

it('mengirim payment_due_date 1440 menit pada create-order tiket', function () {
    fakeCreateOrder([
        'url' => 'https://sandbox.doku.com/checkout/def',
        'expired_datetime' => '20300101123045',
    ]);

    $ticket = expiryTicket();
    $booking = expiryTicketBooking(expiryUser(), $ticket);

    $this->actingAs($booking->user)
        ->get(route('checkout', $booking->id))
        ->assertOk();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/checkout/v1/payment')
            && str_contains($request->body(), '"payment_due_date":1440');
    });

    expect($booking->fresh()->payment_expired_at)->not->toBeNull();
});

it('menghormati override DOKU_PAYMENT_DUE_DATE_MINUTES', function () {
    config(['services.doku.payment_due_date_minutes' => 60]);

    expect(app(DokuExpiry::class)->paymentDueDateMinutes())->toBe(60);
});

/*
|--------------------------------------------------------------------------
| P1: Scheduler hotel hanya membatalkan yang sudah jatuh tempo
|--------------------------------------------------------------------------
*/

it('membatalkan reservasi hotel yang sudah melewati expiry dan mengembalikan kuota (idempoten)', function () {
    $room = expiryRoom(quota: 3);
    $reservation = expiryReservation(expiryUser(), $room, [
        'invoice_number' => 'RC-HTL-9-1700000000',
        'payment_expired_at' => now()->subMinute(),
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(
            expiryCheckStatus($reservation->invoice_number, 'EXPIRED', 'ORDER_EXPIRED'), 200
        ),
    ]);

    $this->artisan('hotel-reservations:cancel-expired')->assertSuccessful();
    $this->artisan('hotel-reservations:cancel-expired')->assertSuccessful();

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($reservation->fresh()->cancelled_at)->not->toBeNull()
        ->and($room->fresh()->quota)->toBe(5);
});

it('TIDAK membatalkan reservasi hotel yang belum jatuh tempo', function () {
    $room = expiryRoom(quota: 3);
    $reservation = expiryReservation(expiryUser(), $room, [
        'invoice_number' => 'RC-HTL-10-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    Http::fake();

    $this->artisan('hotel-reservations:cancel-expired')->assertSuccessful();

    expect($reservation->fresh()->status)->toBe('pending')
        ->and($room->fresh()->quota)->toBe(3);
    Http::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| P2: Pembatalan manual hotel (tombol peserta) & idempotensi
|--------------------------------------------------------------------------
*/

it('mengembalikan kuota hotel hanya sekali saat cancelHotel dipanggil dua kali', function () {
    $room = expiryRoom(quota: 4);
    $reservation = expiryReservation(expiryUser(), $room);
    $service = app(BookingCancellationService::class);

    expect($service->cancelHotel($reservation, 'uji'))->toBeTrue()
        ->and($service->cancelHotel($reservation, 'uji'))->toBeFalse()
        ->and($room->fresh()->quota)->toBe(6);
});

it('pembatalan manual hotel sukses dan tidak memanggil DOKU saat fitur cancel nonaktif', function () {
    Http::fake();

    $room = expiryRoom(quota: 3);
    $reservation = expiryReservation(expiryUser(), $room, [
        'invoice_number' => 'RC-HTL-11-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    $this->actingAs($reservation->user)
        ->post(route('hotels.cancel', $reservation->id))
        ->assertRedirect(route('hotels.index'));

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($room->fresh()->quota)->toBe(5);
    Http::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| P2: Cancel Order API DOKU (best-effort)
|--------------------------------------------------------------------------
*/

it('hotel tanpa channel: pembatalan lokal berjalan tanpa memanggil DOKU Cancel Order', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $room = expiryRoom(quota: 3);
    $reservation = expiryReservation(expiryUser(), $room, [
        'invoice_number' => 'RC-HTL-12-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(
            expiryCheckStatus($reservation->invoice_number, 'PENDING', 'ORDER_GENERATED', 'orig-abc-123'), 200
        ),
        'api-sandbox.doku.com/checkout/v3/cancellations' => Http::response(['message' => 'OK'], 200),
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

it('tidak membatalkan order DOKU yang sudah PAID (tetap batalkan lokal tanpa klaim sukses remote)', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $room = expiryRoom(quota: 3);
    $reservation = expiryReservation(expiryUser(), $room, [
        'invoice_number' => 'RC-HTL-13-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(
            expiryCheckStatus($reservation->invoice_number, 'SUCCESS', 'ORDER_GENERATED', 'orig-xyz'), 200
        ),
        'api-sandbox.doku.com/checkout/v3/cancellations' => Http::response(['message' => 'OK'], 200),
    ]);

    $this->actingAs($reservation->user)
        ->post(route('hotels.cancel', $reservation->id))
        ->assertRedirect(route('hotels.index'));

    expect($reservation->fresh()->status)->toBe('cancelled');

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), '/checkout/v3/cancellations');
    });
});

it('tetap membatalkan lokal saat DOKU Cancel Order gagal (HTTP 500)', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $room = expiryRoom(quota: 3);
    $reservation = expiryReservation(expiryUser(), $room, [
        'invoice_number' => 'RC-HTL-14-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(
            expiryCheckStatus($reservation->invoice_number, 'PENDING', 'ORDER_GENERATED', 'orig-500', 200, [
                'service' => ['id' => 'VIRTUAL_ACCOUNT'],
                'channel' => ['id' => 'VIRTUAL_ACCOUNT_BNI'],
            ]), 200
        ),
        'api-sandbox.doku.com/checkout/v3/cancellations' => Http::response(['error' => 'boom'], 500),
    ]);

    $this->actingAs($reservation->user)
        ->post(route('hotels.cancel', $reservation->id))
        ->assertRedirect(route('hotels.index'));

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($room->fresh()->quota)->toBe(5);
});

it('tetap membatalkan lokal saat DOKU Cancel Order timeout/exception', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $room = expiryRoom(quota: 3);
    $reservation = expiryReservation(expiryUser(), $room, [
        'invoice_number' => 'RC-HTL-15-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    Http::fake(function ($request) use ($reservation) {
        if (str_contains($request->url(), '/checkout/v3/cancellations')) {
            throw new ConnectionException('timeout');
        }

        if (str_contains($request->url(), '/orders/v1/status/')) {
            return Http::response(
                expiryCheckStatus($reservation->invoice_number, 'PENDING', 'ORDER_GENERATED', 'orig-timeout', 200, [
                    'service' => ['id' => 'VIRTUAL_ACCOUNT'],
                    'channel' => ['id' => 'VIRTUAL_ACCOUNT_BNI'],
                ]), 200
            );
        }

        return Http::response([], 200);
    });

    $this->actingAs($reservation->user)
        ->post(route('hotels.cancel', $reservation->id))
        ->assertRedirect(route('hotels.index'));

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($room->fresh()->quota)->toBe(5);
});

it('tidak mengklaim sukses remote bila original_request_id tidak tersedia', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $room = expiryRoom(quota: 3);
    $reservation = expiryReservation(expiryUser(), $room, [
        'invoice_number' => 'RC-HTL-16-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(
            expiryCheckStatus($reservation->invoice_number, 'PENDING', 'ORDER_GENERATED', null, 200, [
                'service' => ['id' => 'VIRTUAL_ACCOUNT'],
                'channel' => ['id' => 'VIRTUAL_ACCOUNT_BNI'],
            ]), 200
        ),
        'api-sandbox.doku.com/checkout/v3/cancellations' => Http::response(['message' => 'OK'], 200),
    ]);

    $result = app(DokuCancelService::class)->cancelInvoice($reservation->invoice_number);

    expect($result['attempted'])->toBeFalse()
        ->and($result['success'])->toBeFalse()
        ->and($result['reason'])->toBe('missing_original_request_id');

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), '/checkout/v3/cancellations');
    });
});

/*
|--------------------------------------------------------------------------
| P2: Pembayaran sukses setelah reservasi dibatalkan (visible follow-up)
|--------------------------------------------------------------------------
*/

it('menahan pembayaran hotel yang masuk setelah reservasi dibatalkan tanpa mengubah status/kuota', function () {
    $room = expiryRoom(quota: 4);
    $reservation = expiryReservation(expiryUser(), $room, [
        'invoice_number' => 'RC-HTL-17-1700000000',
        'status' => 'cancelled',
        'cancelled_at' => now(),
    ]);

    $body = json_encode([
        'order' => ['invoice_number' => $reservation->invoice_number, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'SUCCESS'],
    ]);
    $clientId = config('services.doku.client_id');
    $requestId = (string) Str::uuid();
    $timestamp = gmdate('Y-m-d\TH:i:s\Z');
    $target = '/api/doku/notification';
    $digest = base64_encode(hash('sha256', $body, true));
    $raw = 'Client-Id:'.$clientId."\n".'Request-Id:'.$requestId."\n".'Request-Timestamp:'.$timestamp."\n".'Request-Target:'.$target."\n".'Digest:'.$digest;
    $signature = 'HMACSHA256='.base64_encode(hash_hmac('sha256', $raw, config('services.doku.secret_key'), true));

    $response = $this->call('POST', $target, [], [], [], [
        'HTTP_CLIENT_ID' => $clientId,
        'HTTP_REQUEST_ID' => $requestId,
        'HTTP_REQUEST_TIMESTAMP' => $timestamp,
        'HTTP_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], $body);

    $response->assertStatus(200)->assertJson(['status' => 'HELD']);

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($room->fresh()->quota)->toBe(4);
});

/*
|--------------------------------------------------------------------------
| P2.7: Jalur admin (atomik, idempoten, cancelled_at)
|--------------------------------------------------------------------------
*/

it('admin membatalkan reservasi pending: kuota kembali sekali dan cancelled_at terisi', function () {
    $admin = expiryUser('admin');
    $room = expiryRoom(quota: 3);
    $reservation = expiryReservation(expiryUser(), $room, [
        'payment_expired_at' => now()->addHour(),
    ]);

    $url = route('admin.hotels.reservations.status', $reservation->id);

    $this->actingAs($admin)->post($url, ['status' => 'cancelled'])->assertRedirect();
    // Submit kedua (idempoten): tidak boleh menambah kuota lagi.
    $this->actingAs($admin)->post($url, ['status' => 'cancelled'])->assertRedirect();

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($reservation->fresh()->cancelled_at)->not->toBeNull()
        ->and($room->fresh()->quota)->toBe(5);
});

/*
|--------------------------------------------------------------------------
| P3: Pembatalan manual tiket (best-effort Cancel Order)
|--------------------------------------------------------------------------
*/

it('pembatalan manual tiket tidak memanggil DOKU saat fitur cancel nonaktif', function () {
    Http::fake();

    $ticket = expiryTicket(quota: 3);
    $booking = expiryTicketBooking(expiryUser(), $ticket, [
        'invoice_number' => 'RC-BACT-TIX-29-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    $this->actingAs($booking->user)
        ->from(route('booking.index'))
        ->post(route('booking.cancel', $booking->id))
        ->assertRedirect(route('booking.index'));

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);
    Http::assertNothingSent();
});

it('tiket tanpa channel: pembatalan lokal berjalan tanpa memanggil DOKU Cancel Order', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $ticket = expiryTicket(quota: 3);
    $booking = expiryTicketBooking(expiryUser(), $ticket, [
        'invoice_number' => 'RC-BACT-TIX-30-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(
            expiryCheckStatus($booking->invoice_number, 'PENDING', 'ORDER_GENERATED', 'orig-ticket-30'), 200
        ),
        'api-sandbox.doku.com/checkout/v3/cancellations' => Http::response(['message' => 'OK'], 200),
    ]);

    $this->actingAs($booking->user)
        ->from(route('booking.index'))
        ->post(route('booking.cancel', $booking->id))
        ->assertRedirect(route('booking.index'));

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), '/checkout/v3/cancellations');
    });
});

it('mencoba DOKU Cancel Order untuk tiket dengan channel terpilih', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $ticket = expiryTicket(quota: 3);
    $booking = expiryTicketBooking(expiryUser(), $ticket, [
        'invoice_number' => 'RC-BACT-TIX-31-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(
            expiryCheckStatus($booking->invoice_number, 'PENDING', 'ORDER_GENERATED', 'orig-ticket-31', 200, [
                'service' => ['id' => 'VIRTUAL_ACCOUNT'],
                'channel' => ['id' => 'BCA'],
            ]), 200
        ),
        'api-sandbox.doku.com/checkout/v3/cancellations' => Http::response(['message' => 'OK'], 200),
    ]);

    $this->actingAs($booking->user)
        ->from(route('booking.index'))
        ->post(route('booking.cancel', $booking->id))
        ->assertRedirect(route('booking.index'));

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);

    Http::assertSent(function ($request) use ($booking) {
        return str_contains($request->url(), '/checkout/v3/cancellations')
            && str_contains($request->body(), $booking->invoice_number)
            && str_contains($request->body(), 'orig-ticket-31');
    });
});

it('tetap membatalkan tiket lokal saat DOKU Cancel Order gagal (HTTP 500)', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $ticket = expiryTicket(quota: 3);
    $booking = expiryTicketBooking(expiryUser(), $ticket, [
        'invoice_number' => 'RC-BACT-TIX-32-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(
            expiryCheckStatus($booking->invoice_number, 'PENDING', 'ORDER_GENERATED', 'orig-ticket-32', 200, [
                'service' => ['id' => 'VIRTUAL_ACCOUNT'],
                'channel' => ['id' => 'VIRTUAL_ACCOUNT_BNI'],
            ]), 200
        ),
        'api-sandbox.doku.com/checkout/v3/cancellations' => Http::response(['error' => 'boom'], 500),
    ]);

    $this->actingAs($booking->user)
        ->from(route('booking.index'))
        ->post(route('booking.cancel', $booking->id))
        ->assertRedirect(route('booking.index'));

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/checkout/v3/cancellations');
    });
});

it('tidak memanggil DOKU Cancel Order untuk tiket yang sudah PAID/EXPIRED', function (string $transactionStatus, string $orderStatus) {
    config(['services.doku.cancel_order_enabled' => true]);

    $ticket = expiryTicket(quota: 3);
    $booking = expiryTicketBooking(expiryUser(), $ticket, [
        'invoice_number' => 'RC-BACT-TIX-33-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(
            expiryCheckStatus($booking->invoice_number, $transactionStatus, $orderStatus, 'orig-ticket-33'), 200
        ),
        'api-sandbox.doku.com/checkout/v3/cancellations' => Http::response(['message' => 'OK'], 200),
    ]);

    $this->actingAs($booking->user)
        ->from(route('booking.index'))
        ->post(route('booking.cancel', $booking->id))
        ->assertRedirect(route('booking.index'));

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), '/checkout/v3/cancellations');
    });
})->with([
    'PAID' => ['SUCCESS', 'ORDER_GENERATED'],
    'transaction EXPIRED' => ['EXPIRED', 'ORDER_EXPIRED'],
    'order EXPIRED' => ['PENDING', 'ORDER_EXPIRED'],
]);

it('tidak memanggil DOKU Cancel Order dua kali saat pembatalan tiket diulang', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $ticket = expiryTicket(quota: 3);
    $booking = expiryTicketBooking(expiryUser(), $ticket, [
        'invoice_number' => 'RC-BACT-TIX-34-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(
            expiryCheckStatus($booking->invoice_number, 'PENDING', 'ORDER_GENERATED', 'orig-ticket-34', 200, [
                'service' => ['id' => 'VIRTUAL_ACCOUNT'],
                'channel' => ['id' => 'VIRTUAL_ACCOUNT_BNI'],
            ]), 200
        ),
        'api-sandbox.doku.com/checkout/v3/cancellations' => Http::response(['message' => 'OK'], 200),
    ]);

    $this->actingAs($booking->user)
        ->from(route('booking.index'))
        ->post(route('booking.cancel', $booking->id))
        ->assertRedirect(route('booking.index'));

    $this->actingAs($booking->user)
        ->from(route('booking.index'))
        ->post(route('booking.cancel', $booking->id))
        ->assertRedirect(route('booking.index'));

    $cancelCalls = Http::recorded()
        ->filter(fn ($pair) => str_contains($pair[0]->url(), '/checkout/v3/cancellations'))
        ->count();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4)
        ->and($cancelCalls)->toBe(1);
});

it('mencoba DOKU Cancel Order untuk hotel dengan channel terpilih', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $room = expiryRoom(quota: 3);
    $reservation = expiryReservation(expiryUser(), $room, [
        'invoice_number' => 'RC-HTL-18-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(
            expiryCheckStatus($reservation->invoice_number, 'PENDING', 'ORDER_GENERATED', 'orig-hotel-18', 200, [
                'service' => ['id' => 'QRIS'],
                'channel' => ['id' => 'QRIS'],
            ]), 200
        ),
        'api-sandbox.doku.com/checkout/v3/cancellations' => Http::response(['message' => 'OK'], 200),
    ]);

    $this->actingAs($reservation->user)
        ->post(route('hotels.cancel', $reservation->id))
        ->assertRedirect(route('hotels.index'));

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($room->fresh()->quota)->toBe(5);

    Http::assertSent(function ($request) use ($reservation) {
        return str_contains($request->url(), '/checkout/v3/cancellations')
            && str_contains($request->body(), $reservation->invoice_number)
            && str_contains($request->body(), 'orig-hotel-18');
    });
});

it('tidak memanggil DOKU Cancel Order dua kali saat pembatalan hotel diulang', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $room = expiryRoom(quota: 3);
    $reservation = expiryReservation(expiryUser(), $room, [
        'invoice_number' => 'RC-HTL-19-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(
            expiryCheckStatus($reservation->invoice_number, 'PENDING', 'ORDER_GENERATED', 'orig-hotel-19', 200, [
                'service' => ['id' => 'QRIS'],
                'channel' => ['id' => 'QRIS'],
            ]), 200
        ),
        'api-sandbox.doku.com/checkout/v3/cancellations' => Http::response(['message' => 'OK'], 200),
    ]);

    $this->actingAs($reservation->user)
        ->from(route('hotels.index'))
        ->post(route('hotels.cancel', $reservation->id))
        ->assertRedirect(route('hotels.index'));

    $this->actingAs($reservation->user)
        ->from(route('hotels.index'))
        ->post(route('hotels.cancel', $reservation->id))
        ->assertRedirect(route('hotels.index'));

    $cancelCalls = Http::recorded()
        ->filter(fn ($pair) => str_contains($pair[0]->url(), '/checkout/v3/cancellations'))
        ->count();

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($room->fresh()->quota)->toBe(5)
        ->and($cancelCalls)->toBe(1);
});

it('menandai Cancel Order terkonfirmasi setelah verifikasi DOKU menunjukkan CANCELLED', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $invoice = 'RC-BACT-CONF-1-1700000000';
    $statusCalls = 0;

    Http::fake(function ($request) use ($invoice, &$statusCalls) {
        if (str_contains($request->url(), '/checkout/v3/cancellations')) {
            return Http::response(['message' => 'OK'], 200);
        }

        $statusCalls++;

        return Http::response(expiryCheckStatus(
            $invoice,
            'PENDING',
            $statusCalls === 1 ? 'ORDER_GENERATED' : 'CANCELLED',
            'orig-confirmed',
            200,
            [
                'service' => ['id' => 'VIRTUAL_ACCOUNT'],
                'channel' => ['id' => 'VIRTUAL_ACCOUNT_BNI'],
            ]
        ), 200);
    });

    $result = app(DokuCancelService::class)->cancelInvoice($invoice);

    expect($result['attempted'])->toBeTrue()
        ->and($result['success'])->toBeTrue()
        ->and($result['confirmed'])->toBeTrue()
        ->and($result['reason'])->toBe('cancelled')
        ->and($statusCalls)->toBe(2);
});

it('tidak mengklaim CANCELLED bila verifikasi DOKU masih PENDING', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $invoice = 'RC-BACT-CONF-2-1700000000';

    Http::fake(function ($request) use ($invoice) {
        if (str_contains($request->url(), '/checkout/v3/cancellations')) {
            return Http::response(['message' => 'OK'], 200);
        }

        return Http::response(expiryCheckStatus(
            $invoice,
            'PENDING',
            'ORDER_GENERATED',
            'orig-unconfirmed',
            200,
            [
                'service' => ['id' => 'VIRTUAL_ACCOUNT'],
                'channel' => ['id' => 'VIRTUAL_ACCOUNT_BNI'],
            ]
        ), 200);
    });

    $result = app(DokuCancelService::class)->cancelInvoice($invoice);

    expect($result['attempted'])->toBeTrue()
        ->and($result['success'])->toBeTrue()
        ->and($result['confirmed'])->toBeFalse()
        ->and($result['reason'])->toBe('cancelled_unconfirmed');
});

it('tidak memanggil Cancel Order bila Check Status gagal menentukan channel', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $invoice = 'RC-BACT-UNKNOWN-1-1700000000';

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(['error' => 'boom'], 500),
        'api-sandbox.doku.com/checkout/v3/cancellations' => Http::response(['message' => 'OK'], 200),
    ]);

    $result = app(DokuCancelService::class)->cancelInvoice($invoice);

    expect($result['attempted'])->toBeFalse()
        ->and($result['success'])->toBeFalse()
        ->and($result['reason'])->toBe('channel_unknown');

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), '/checkout/v3/cancellations');
    });
});

it('tidak mencoba mengubah order DOKU yang sudah CANCELLED', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $invoice = 'RC-BACT-CANCELLED-1-1700000000';

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(
            expiryCheckStatus($invoice, 'PENDING', 'CANCELLED', 'orig-cancelled', 200, [
                'service' => ['id' => 'VIRTUAL_ACCOUNT'],
                'channel' => ['id' => 'VIRTUAL_ACCOUNT_BNI'],
            ]), 200
        ),
        'api-sandbox.doku.com/checkout/v3/cancellations' => Http::response(['message' => 'OK'], 200),
    ]);

    $result = app(DokuCancelService::class)->cancelInvoice($invoice);

    expect($result['attempted'])->toBeFalse()
        ->and($result['success'])->toBeTrue()
        ->and($result['confirmed'])->toBeTrue()
        ->and($result['reason'])->toBe('already_cancelled');

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), '/checkout/v3/cancellations');
    });
});

it('admin membatalkan tiket pending: kuota kembali dan Cancel Order DOKU dicoba sekali', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $admin = expiryUser('admin');
    $ticket = expiryTicket(quota: 3);
    $booking = expiryTicketBooking(expiryUser(), $ticket, [
        'invoice_number' => 'RC-BACT-TIX-ADM-1-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(
            expiryCheckStatus($booking->invoice_number, 'PENDING', 'ORDER_GENERATED', 'orig-admin-ticket', 200, [
                'service' => ['id' => 'VIRTUAL_ACCOUNT'],
                'channel' => ['id' => 'VIRTUAL_ACCOUNT_BNI'],
            ]), 200
        ),
        'api-sandbox.doku.com/checkout/v3/cancellations' => Http::response(['message' => 'OK'], 200),
    ]);

    $payload = [
        'status' => 'cancelled',
        'full_name' => $booking->full_name,
        'name_with_title' => $booking->name_with_title,
        'gmail_account' => $booking->gmail_account,
        'whatsapp_number' => $booking->whatsapp_number,
        'nik' => $booking->nik,
        'profession' => $booking->profession,
        'institution_name' => $booking->institution_name,
        'institution_province' => $booking->institution_province,
        'institution_city' => $booking->institution_city,
        'notes' => 'Dibatalkan oleh panitia',
    ];

    $url = route('admin.participants.updateStatus', $booking->id);

    $this->actingAs($admin)->post($url, $payload)->assertRedirect();
    // Submit kedua: status sudah cancelled, tidak boleh memanggil/mengembalikan lagi.
    $this->actingAs($admin)->post($url, $payload)->assertRedirect();

    $cancelCalls = Http::recorded()
        ->filter(fn ($pair) => str_contains($pair[0]->url(), '/checkout/v3/cancellations'))
        ->count();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4)
        ->and($cancelCalls)->toBe(1);
});

it('admin menonaktifkan tiket: booking pending dibatalkan dan Cancel Order DOKU dicoba', function () {
    config(['services.doku.cancel_order_enabled' => true]);

    $admin = expiryUser('admin');
    $ticket = expiryTicket(quota: 2);
    $booking = expiryTicketBooking(expiryUser(), $ticket, [
        'invoice_number' => 'RC-BACT-TIX-ADM-2-1700000000',
        'payment_expired_at' => now()->addHour(),
    ]);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(
            expiryCheckStatus($booking->invoice_number, 'PENDING', 'ORDER_GENERATED', 'orig-admin-bulk', 200, [
                'service' => ['id' => 'VIRTUAL_ACCOUNT'],
                'channel' => ['id' => 'VIRTUAL_ACCOUNT_BNI'],
            ]), 200
        ),
        'api-sandbox.doku.com/checkout/v3/cancellations' => Http::response(['message' => 'OK'], 200),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.tickets.toggle', $ticket->id))
        ->assertOk();

    expect((bool) $ticket->fresh()->is_active)->toBeFalse()
        ->and($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(3);

    Http::assertSent(function ($request) use ($booking) {
        return str_contains($request->url(), '/checkout/v3/cancellations')
            && str_contains($request->body(), $booking->invoice_number)
            && str_contains($request->body(), 'orig-admin-bulk');
    });
});
