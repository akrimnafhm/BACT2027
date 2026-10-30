<?php

use App\Mail\TicketPaidMail;
use App\Models\HotelReservation;
use App\Models\HotelRoom;
use App\Models\NotificationTemplate;
use App\Models\Ticket;
use App\Models\TicketBooking;
use App\Models\User;
use App\Services\BookingCancellationService;
use App\Services\DokuReconciliationService;
use App\Services\HotelNotificationService;
use App\Services\TicketNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.doku.client_id' => 'TEST-CLIENT-ID',
        'services.doku.secret_key' => 'TEST-SECRET-KEY',
        'services.doku.is_production' => false,
        'services.doku.notification_url' => 'http://localhost/api/doku/notification',
        'services.doku.check_status_cache_seconds' => 0,
    ]);

    Mail::fake();
});

function dokuUser(): User
{
    return tap(User::create([
        'name' => 'Peserta DOKU',
        'email' => 'doku@test.com',
        'password' => bcrypt('password123'),
        'nik' => '3201010101010002',
        'gender' => 'Laki-laki',
        'phone_number' => '081234567891',
    ]), function (User $user) {
        $user->forceFill(['email_verified_at' => now()])->save();
    });
}

function dokuTicket(int $quota = 5): Ticket
{
    return Ticket::create([
        'ticket_name' => 'Basic',
        'ticket_category' => 'Basic',
        'price' => 100000,
        'quota' => $quota,
    ]);
}

function dokuBooking(User $user, Ticket $ticket, array $overrides = []): TicketBooking
{
    return TicketBooking::create(array_merge([
        'user_id' => $user->id,
        'ticket_id' => $ticket->id,
        'status' => 'pending',
        'source' => 'website',
        'ticket_name' => $ticket->ticket_name,
        'ticket_category' => $ticket->ticket_category,
        'amount' => $ticket->price,
        'invoice_number' => 'RC-BACT-1-1700000000',
        'payment_expired_at' => now()->subMinute(),
        'full_name' => 'Peserta DOKU',
        'name_with_title' => 'Peserta DOKU, S.Ked',
        'nik' => '3201010101010002',
        'profession' => 'Dokter',
        'whatsapp_number' => '081234567891',
        'gmail_account' => 'doku@test.com',
        'plataran_sehat_email' => 'doku@test.com',
        'institution_name' => 'RS Test',
        'institution_city' => 'Bandung',
        'institution_province' => 'Jawa Barat',
    ], $overrides));
}

function dokuRoom(int $quota = 5): HotelRoom
{
    return HotelRoom::create([
        'room_type' => 'Deluxe King',
        'price_per_night' => 500000,
        'quota' => $quota,
        'is_active' => true,
    ]);
}

function dokuReservation(User $user, HotelRoom $room, array $overrides = []): HotelReservation
{
    return HotelReservation::create(array_merge([
        'booking_code' => 'HTL-1',
        'user_id' => $user->id,
        'hotel_room_id' => $room->id,
        'check_in' => '2027-01-18',
        'check_out' => '2027-01-20',
        'total_nights' => 2,
        'quantity' => 2,
        'total_price' => 2000000,
        'guest_name' => 'Peserta DOKU',
        'guest_email' => 'doku@test.com',
        'guest_phone' => '081234567891',
        'status' => 'pending',
        'invoice_number' => 'RC-HTL-1-1700000000',
        'payment_expired_at' => now()->subMinute(),
    ], $overrides));
}

/**
 * Kirim notifikasi DOKU dengan signature valid.
 */
function postDokuNotification(array $payload, ?string $secret = null)
{
    $body = json_encode($payload);
    $clientId = config('services.doku.client_id');
    $secret = $secret ?? config('services.doku.secret_key');
    $requestId = (string) Str::uuid();
    $timestamp = gmdate('Y-m-d\TH:i:s\Z');
    $target = '/api/doku/notification';
    $digest = base64_encode(hash('sha256', $body, true));

    $raw = 'Client-Id:'.$clientId."\n"
         .'Request-Id:'.$requestId."\n"
         .'Request-Timestamp:'.$timestamp."\n"
         .'Request-Target:'.$target."\n"
         .'Digest:'.$digest;

    $signature = 'HMACSHA256='.base64_encode(hash_hmac('sha256', $raw, $secret, true));

    return test()->call('POST', $target, [], [], [], [
        'HTTP_CLIENT_ID' => $clientId,
        'HTTP_REQUEST_ID' => $requestId,
        'HTTP_REQUEST_TIMESTAMP' => $timestamp,
        'HTTP_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], $body);
}

function fakeCheckStatus(string $invoice, ?string $transactionStatus, ?string $orderStatus, int $httpStatus = 200): void
{
    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response([
            'order' => [
                'invoice_number' => $invoice,
                'status' => $orderStatus,
            ],
            'transaction' => [
                'status' => $transactionStatus,
                'date' => '2027-01-01 10:00:00',
            ],
        ], $httpStatus),
    ]);
}

/*
|--------------------------------------------------------------------------
| Webhook: verifikasi signature
|--------------------------------------------------------------------------
*/

it('menolak notifikasi DOKU dengan signature tidak valid tanpa mengubah booking', function () {
    $booking = dokuBooking(dokuUser(), dokuTicket());

    $response = postDokuNotification([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'SUCCESS'],
    ], secret: 'WRONG-SECRET');

    $response->assertStatus(401);
    expect($booking->fresh()->status)->toBe('pending');
});

it('menandai tiket PAID saat notifikasi SUCCESS dengan signature valid', function () {
    $booking = dokuBooking(dokuUser(), dokuTicket());

    $response = postDokuNotification([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'SUCCESS', 'date' => '2027-01-01 10:00:00'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'SUCCESS']);
    expect($booking->fresh()->status)->toBe('paid');
});

it('mengabaikan notifikasi SUCCESS bila booking sudah PAID (idempoten)', function () {
    $booking = dokuBooking(dokuUser(), dokuTicket(), ['status' => 'paid', 'paid_at' => now()]);

    $response = postDokuNotification([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'SUCCESS'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'IGNORED']);
    expect($booking->fresh()->status)->toBe('paid');
});

it('membatalkan tiket pending dan mengembalikan kuota saat notifikasi ORDER_EXPIRED', function () {
    $ticket = dokuTicket(quota: 3);
    $booking = dokuBooking(dokuUser(), $ticket);

    $response = postDokuNotification([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_EXPIRED'],
        'transaction' => ['status' => 'EXPIRED'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'CANCELLED']);
    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);
});

it('TIDAK membatalkan tiket pada notifikasi PENDING', function () {
    $ticket = dokuTicket(quota: 3);
    $booking = dokuBooking(dokuUser(), $ticket);

    $response = postDokuNotification([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'PENDING'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'IGNORED']);
    expect($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
});

it('TIDAK membatalkan tiket saat order ORDER_RECOVERED', function () {
    $ticket = dokuTicket(quota: 3);
    $booking = dokuBooking(dokuUser(), $ticket);

    postDokuNotification([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_RECOVERED'],
        'transaction' => ['status' => 'EXPIRED'],
    ]);

    expect($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
});

it('TIDAK membatalkan tiket pada transaction FAILED (Checkout masih bisa ganti channel)', function () {
    $booking = dokuBooking(dokuUser(), dokuTicket());

    postDokuNotification([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'FAILED'],
    ]);

    expect($booking->fresh()->status)->toBe('pending');
});

/*
|--------------------------------------------------------------------------
| Check Status API reconciliation (scheduler)
|--------------------------------------------------------------------------
*/

it('menandai PAID dari Check Status API saat SUCCESS', function () {
    $booking = dokuBooking(dokuUser(), dokuTicket());
    fakeCheckStatus($booking->invoice_number, 'SUCCESS', 'ORDER_GENERATED');

    $this->artisan('bookings:cancel-expired')->assertSuccessful();

    expect($booking->fresh()->status)->toBe('paid');
});

it('membatalkan tiket dan mengembalikan kuota dari Check Status API saat EXPIRED', function () {
    $ticket = dokuTicket(quota: 3);
    $booking = dokuBooking(dokuUser(), $ticket);
    fakeCheckStatus($booking->invoice_number, 'EXPIRED', 'ORDER_EXPIRED');

    $this->artisan('bookings:cancel-expired')->assertSuccessful();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);
});

it('MENAHAN tiket saat Check Status API PENDING (tidak dibatalkan, kuota tidak kembali)', function () {
    $ticket = dokuTicket(quota: 3);
    $booking = dokuBooking(dokuUser(), $ticket);
    fakeCheckStatus($booking->invoice_number, 'PENDING', 'ORDER_GENERATED');

    $this->artisan('bookings:cancel-expired')->assertSuccessful();

    expect($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
});

it('MENAHAN tiket saat Check Status API 404 (revisi #3)', function () {
    $ticket = dokuTicket(quota: 3);
    $booking = dokuBooking(dokuUser(), $ticket);
    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response(['message' => 'Not Found'], 404),
    ]);

    $this->artisan('bookings:cancel-expired')->assertSuccessful();

    expect($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
});

it('MENAHAN tiket saat Check Status API gagal koneksi', function () {
    $ticket = dokuTicket(quota: 3);
    $booking = dokuBooking(dokuUser(), $ticket);
    Http::fake(function () {
        throw new ConnectionException('connection refused');
    });

    $this->artisan('bookings:cancel-expired')->assertSuccessful();

    expect($booking->fresh()->status)->toBe('pending')
        ->and($ticket->fresh()->quota)->toBe(3);
});

it('membatalkan tiket tanpa invoice_number secara lokal (belum pernah ke DOKU)', function () {
    $ticket = dokuTicket(quota: 3);
    $booking = dokuBooking(dokuUser(), $ticket, ['invoice_number' => null]);

    $this->artisan('bookings:cancel-expired')->assertSuccessful();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);
});

it('mengembalikan kuota hanya sekali walau rekonsiliasi dijalankan berulang', function () {
    $ticket = dokuTicket(quota: 3);
    $booking = dokuBooking(dokuUser(), $ticket);
    fakeCheckStatus($booking->invoice_number, 'EXPIRED', 'ORDER_EXPIRED');

    $this->artisan('bookings:cancel-expired')->assertSuccessful();
    $this->artisan('bookings:cancel-expired')->assertSuccessful();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);
});

/*
|--------------------------------------------------------------------------
| Hotel reservation reconciliation
|--------------------------------------------------------------------------
*/

it('menandai reservasi hotel PAID dari webhook SUCCESS', function () {
    $room = dokuRoom(quota: 3);
    $reservation = dokuReservation(dokuUser(), $room);

    $response = postDokuNotification([
        'order' => ['invoice_number' => $reservation->invoice_number, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'SUCCESS'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'SUCCESS']);
    expect($reservation->fresh()->status)->toBe('paid');
});

it('membatalkan reservasi hotel dan mengembalikan kuota kamar saat Check Status EXPIRED', function () {
    $room = dokuRoom(quota: 3);
    $reservation = dokuReservation(dokuUser(), $room);
    fakeCheckStatus($reservation->invoice_number, 'EXPIRED', 'ORDER_EXPIRED');

    $this->artisan('hotel-reservations:cancel-expired')->assertSuccessful();

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($room->fresh()->quota)->toBe(5);
});

it('MENAHAN reservasi hotel saat Check Status API PENDING', function () {
    $room = dokuRoom(quota: 3);
    $reservation = dokuReservation(dokuUser(), $room);
    fakeCheckStatus($reservation->invoice_number, 'PENDING', 'ORDER_GENERATED');

    $this->artisan('hotel-reservations:cancel-expired')->assertSuccessful();

    expect($reservation->fresh()->status)->toBe('pending')
        ->and($room->fresh()->quota)->toBe(3);
});

/*
|--------------------------------------------------------------------------
| Idempotensi layanan pembatalan
|--------------------------------------------------------------------------
*/

it('mengembalikan kuota tiket hanya sekali saat cancelTicket dipanggil dua kali', function () {
    $ticket = dokuTicket(quota: 3);
    $booking = dokuBooking(dokuUser(), $ticket);
    $service = app(BookingCancellationService::class);

    expect($service->cancelTicket($booking, 'uji'))->toBeTrue()
        ->and($service->cancelTicket($booking, 'uji'))->toBeFalse()
        ->and($ticket->fresh()->quota)->toBe(4);
});

/*
|--------------------------------------------------------------------------
| Critical: booking final (cancelled/expired) tidak boleh dihidupkan kembali
|--------------------------------------------------------------------------
*/

it('menolak menghidupkan tiket cancelled saat webhook SUCCESS (kuota tetap, tanpa notifikasi)', function () {
    $ticket = dokuTicket(quota: 4);
    $booking = dokuBooking(dokuUser(), $ticket, [
        'status' => 'cancelled',
        'cancelled_at' => now(),
    ]);

    $this->mock(TicketNotificationService::class, fn ($mock) => $mock->shouldNotReceive('sendTicketPaid'));

    $response = postDokuNotification([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'SUCCESS'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'HELD']);

    $fresh = $booking->fresh();
    expect($fresh->status)->toBe('cancelled')
        ->and($fresh->paid_at)->toBeNull()
        ->and($ticket->fresh()->quota)->toBe(4)
        ->and((string) $fresh->notes)->toContain('PERLU TINDAK LANJUT');
});

it('aman saat webhook SUCCESS datang setelah tiket auto-expired (cancelled)', function () {
    $ticket = dokuTicket(quota: 3);
    $booking = dokuBooking(dokuUser(), $ticket);

    app(BookingCancellationService::class)->cancelTicket($booking, 'kedaluwarsa');
    expect($ticket->fresh()->quota)->toBe(4);

    $this->mock(TicketNotificationService::class, fn ($mock) => $mock->shouldNotReceive('sendTicketPaid'));

    $response = postDokuNotification([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_EXPIRED'],
        'transaction' => ['status' => 'SUCCESS'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'HELD']);
    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);
});

it('ORDER_RECOVERED + SUCCESS tidak menghidupkan tiket cancelled', function () {
    $ticket = dokuTicket(quota: 4);
    $booking = dokuBooking(dokuUser(), $ticket, [
        'status' => 'cancelled',
        'cancelled_at' => now(),
    ]);

    $this->mock(TicketNotificationService::class, fn ($mock) => $mock->shouldNotReceive('sendTicketPaid'));

    $response = postDokuNotification([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_RECOVERED'],
        'transaction' => ['status' => 'SUCCESS'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'HELD']);
    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);
});

it('menandai tiket pending PAID saat ORDER_RECOVERED + SUCCESS (pemulihan sah)', function () {
    $booking = dokuBooking(dokuUser(), dokuTicket());

    $response = postDokuNotification([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_RECOVERED'],
        'transaction' => ['status' => 'SUCCESS'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'SUCCESS']);
    expect($booking->fresh()->status)->toBe('paid')
        ->and($booking->fresh()->paid_at)->not->toBeNull();
});

it('tidak menurunkan status booking paid saat webhook ORDER_EXPIRED', function () {
    $ticket = dokuTicket(quota: 3);
    $booking = dokuBooking(dokuUser(), $ticket, ['status' => 'paid', 'paid_at' => now()]);

    $response = postDokuNotification([
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_EXPIRED'],
        'transaction' => ['status' => 'EXPIRED'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'IGNORED']);
    expect($booking->fresh()->status)->toBe('paid')
        ->and($ticket->fresh()->quota)->toBe(3);
});

it('markTicketPaid menolak transisi dari status cancelled (conditional update)', function () {
    $ticket = dokuTicket(quota: 4);
    $booking = dokuBooking(dokuUser(), $ticket, [
        'status' => 'cancelled',
        'cancelled_at' => now(),
    ]);

    $this->mock(TicketNotificationService::class, fn ($mock) => $mock->shouldNotReceive('sendTicketPaid'));

    $result = app(DokuReconciliationService::class)->markTicketPaid($booking);

    expect($result)->toBeFalse()
        ->and($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);
});

it('menolak menghidupkan reservasi hotel cancelled saat webhook SUCCESS (kuota tetap)', function () {
    $room = dokuRoom(quota: 4);
    $reservation = dokuReservation(dokuUser(), $room, [
        'status' => 'cancelled',
        'cancelled_at' => now(),
    ]);

    $this->mock(HotelNotificationService::class, fn ($mock) => $mock->shouldNotReceive('sendHotelPaid'));

    $response = postDokuNotification([
        'order' => ['invoice_number' => $reservation->invoice_number, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'SUCCESS'],
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'HELD']);
    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($room->fresh()->quota)->toBe(4);
});

/*
|--------------------------------------------------------------------------
| High: transisi atomik & notifikasi tidak ganda
|--------------------------------------------------------------------------
*/

it('webhook SUCCESS berulang hanya memicu satu notifikasi tiket', function () {
    $booking = dokuBooking(dokuUser(), dokuTicket());

    $this->mock(TicketNotificationService::class, fn ($mock) => $mock->shouldReceive('sendTicketPaid')->once());

    $payload = [
        'order' => ['invoice_number' => $booking->invoice_number, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'SUCCESS'],
    ];

    postDokuNotification($payload)->assertStatus(200)->assertJson(['status' => 'SUCCESS']);
    postDokuNotification($payload)->assertStatus(200)->assertJson(['status' => 'IGNORED']);

    expect($booking->fresh()->status)->toBe('paid');
});

it('webhook SUCCESS berulang pada reservasi hotel hanya memicu satu notifikasi', function () {
    $room = dokuRoom(quota: 3);
    $reservation = dokuReservation(dokuUser(), $room);

    $this->mock(HotelNotificationService::class, fn ($mock) => $mock->shouldReceive('sendHotelPaid')->once());

    $payload = [
        'order' => ['invoice_number' => $reservation->invoice_number, 'status' => 'ORDER_GENERATED'],
        'transaction' => ['status' => 'SUCCESS'],
    ];

    postDokuNotification($payload)->assertStatus(200)->assertJson(['status' => 'SUCCESS']);
    postDokuNotification($payload)->assertStatus(200)->assertJson(['status' => 'IGNORED']);

    expect($reservation->fresh()->status)->toBe('paid');
});

it('klaim notified_at atomik mencegah notifikasi email ganda', function () {
    NotificationTemplate::create([
        'key' => 'ticket_paid_email',
        'channel' => 'email',
        'label' => 'Tiket Lunas (Email)',
        'subject' => 'Konfirmasi {tiket}',
        'body' => 'Halo {nama}, tiket {kode_tiket} sudah lunas.',
        'is_active' => true,
    ]);

    $booking = dokuBooking(dokuUser(), dokuTicket(), ['status' => 'paid', 'paid_at' => now()]);

    $service = app(TicketNotificationService::class);
    $service->sendTicketPaid($booking->fresh());
    $service->sendTicketPaid($booking->fresh());

    Mail::assertSent(TicketPaidMail::class, 1);
    expect($booking->fresh()->notified_at)->not->toBeNull();
});
