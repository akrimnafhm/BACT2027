<?php

use App\Models\Ticket;
use App\Models\TicketBooking;
use App\Models\User;
use App\Services\DokuApiService;
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
    ]);
});

function evidenceBooking(string $invoice): TicketBooking
{
    $user = tap(User::create([
        'name' => 'Peserta Bukti',
        'email' => 'bukti-'.Str::random(6).'@test.com',
        'password' => bcrypt('password123'),
        'nik' => '3201010101'.random_int(100000, 999999),
        'gender' => 'Laki-laki',
        'phone_number' => '0812'.random_int(100000, 999999),
    ]), function (User $user) {
        $user->forceFill(['email_verified_at' => now()])->save();
    });

    $ticket = Ticket::create([
        'ticket_name' => 'Basic',
        'ticket_category' => 'Basic',
        'price' => 100000,
        'quota' => 5,
    ]);

    return TicketBooking::create([
        'user_id' => $user->id,
        'ticket_id' => $ticket->id,
        'status' => 'pending',
        'source' => 'website',
        'ticket_name' => $ticket->ticket_name,
        'ticket_category' => $ticket->ticket_category,
        'amount' => $ticket->price,
        'invoice_number' => $invoice,
        'payment_expired_at' => now()->subMinute(),
        'full_name' => 'Peserta Bukti',
        'name_with_title' => 'Peserta Bukti',
        'nik' => '3201010101010003',
        'profession' => 'Dokter',
        'whatsapp_number' => '081234567892',
        'gmail_account' => 'bukti@test.com',
        'plataran_sehat_email' => 'bukti@test.com',
        'institution_name' => 'RS Test',
        'institution_city' => 'Bandung',
        'institution_province' => 'Jawa Barat',
    ]);
}

function fakeStatus(string $txn, ?string $order): void
{
    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response([
            'order' => ['invoice_number' => 'INV', 'status' => $order],
            'transaction' => ['status' => $txn, 'date' => '2027-01-01 10:00:00'],
            'service' => ['id' => 'VIRTUAL_ACCOUNT'],
            'channel' => ['id' => 'VIRTUAL_ACCOUNT_BSI'],
            'virtual_account_info' => ['expired_date' => '20270101120000'],
        ], 200),
    ]);
}

it('DOKU_CHECK_STATUS_CACHE_SECONDS=0 benar-benar mem-bypass cache', function () {
    config(['services.doku.check_status_cache_seconds' => 0]);
    fakeStatus('PENDING', 'ORDER_GENERATED');

    app(DokuApiService::class)->checkStatus('RC-BACT-1-1700000000');
    app(DokuApiService::class)->checkStatus('RC-BACT-1-1700000000');

    Http::assertSentCount(2);
});

it('cache aktif menahan request duplikat Check Status', function () {
    config(['services.doku.check_status_cache_seconds' => 120]);
    fakeStatus('PENDING', 'ORDER_GENERATED');

    app(DokuApiService::class)->checkStatus('RC-BACT-2-1700000000');
    app(DokuApiService::class)->checkStatus('RC-BACT-2-1700000000');

    Http::assertSentCount(1);
});

it('checkStatus mencatat service/channel/expired_date untuk bukti uji', function () {
    fakeStatus('PENDING', 'ORDER_EXPIRED');

    $result = app(DokuApiService::class)->checkStatus('RC-BACT-3-1700000000', fresh: true);

    expect($result['transaction_status'])->toBe('PENDING')
        ->and($result['order_status'])->toBe('ORDER_EXPIRED')
        ->and($result['service_id'])->toBe('VIRTUAL_ACCOUNT')
        ->and($result['channel_id'])->toBe('VIRTUAL_ACCOUNT_BSI')
        ->and($result['expired_date'])->toBe('20270101120000')
        ->and($result['outcome'])->toBe('expired');
});

it('doku:inspect read-only: mencetak ringkasan tanpa mengubah data', function () {
    $invoice = 'RC-BACT-4-1700000000';
    $booking = evidenceBooking($invoice);
    fakeStatus('PENDING', 'ORDER_EXPIRED');

    $this->artisan('doku:inspect', ['invoice' => $invoice])
        ->expectsOutputToContain('transaction.status: PENDING')
        ->expectsOutputToContain('order.status ......: ORDER_EXPIRED')
        ->expectsOutputToContain('status lokal tiket : pending')
        ->assertSuccessful();

    // Tanpa migrasi kolom bukti: status lokal tidak berubah, tanpa error.
    expect($booking->fresh()->status)->toBe('pending');
});

it('paymentDueDateMinutes di-clamp ke 1440 bila kecil di production', function () {
    config(['services.doku.is_production' => true, 'services.doku.payment_due_date_minutes' => 2]);

    expect(app(DokuExpiry::class)->paymentDueDateMinutes())->toBe(1440);
});

it('paymentDueDateMinutes mengizinkan nilai kecil di luar production (sandbox)', function () {
    config(['services.doku.is_production' => false, 'services.doku.payment_due_date_minutes' => 2]);

    expect(app(DokuExpiry::class)->paymentDueDateMinutes())->toBe(2);
});
