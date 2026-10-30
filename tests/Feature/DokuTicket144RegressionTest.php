<?php

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
        'services.doku.payment_due_date_minutes' => 2,
        'services.doku.local_expiry_fallback_hours' => 24,
        'services.doku.cancel_order_enabled' => false,
        'services.doku.hold_grace_minutes' => 15,
        'services.doku.lapse_buffer_seconds' => 90,
    ]);
});

/*
 * Reproduksi kasus produksi RC-BACT-144-1792039649 (due 2 menit, tanpa channel):
 * - 21 dtk setelah due date, Check Status masih PENDING + ORDER_GENERATED
 *   -> HELD adalah HASIL BENAR (buffer 90 dtk belum lewat), bukan bug.
 * - Setelah cutoff (due + buffer), reconcile berikutnya -> cancelled_lapsed.
 * - Klik "lanjutkan pembayaran" sebelum cutoff -> pesan verifikasi;
 *   sesudah cutoff -> pesan kedaluwarsa (kartu pending ikut hilang).
 */
it('regresi 144: HOLD sebelum cutoff, cancelled_lapsed sesudahnya', function () {
    $user = tap(User::create([
        'name' => 'Peserta 144',
        'email' => 't144-'.Str::random(6).'@test.com',
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
        'quota' => 3,
    ]);

    $makeBooking = function () use ($user, $ticket) {
        return TicketBooking::create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending',
            'source' => 'website',
            'ticket_name' => $ticket->ticket_name,
            'ticket_category' => $ticket->ticket_category,
            'amount' => $ticket->price,
            'invoice_number' => 'RC-BACT-144-1700000000',
            'payment_expired_at' => now()->subSeconds(30),
            'payment_url' => 'https://sandbox.doku.com/checkout/144',
            'full_name' => 'Peserta 144',
            'name_with_title' => 'Peserta 144',
            'nik' => '3201010101010003',
            'profession' => 'Dokter',
            'whatsapp_number' => '081234567892',
            'gmail_account' => 't144@test.com',
            'plataran_sehat_email' => 't144@test.com',
            'institution_name' => 'RS Test',
            'institution_city' => 'Bandung',
            'institution_province' => 'Jawa Barat',
        ]);
    };

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response([
            'order' => ['invoice_number' => 'RC-BACT-144-1700000000', 'status' => 'ORDER_GENERATED'],
            'transaction' => ['status' => 'PENDING', 'date' => '2027-01-01 10:00:00'],
        ], 200),
    ]);

    $booking = $makeBooking();

    // 30 dtk setelah due date (< buffer 90 dtk): HOLD + pesan verifikasi, kartu tetap ada.
    $action = app(DokuReconciliationService::class)->reconcileTicket($booking);
    expect($action)->toBe(DokuReconciliationService::ACTION_HELD)
        ->and($booking->fresh()->status)->toBe('pending');

    $this->actingAs($user)
        ->get(route('checkout', $booking->id))
        ->assertRedirect(route('booking.index'))
        ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'Waktu pembayaran telah berakhir'));

    // 5 menit setelah due date (> buffer): cancelled_lapsed + pesan kedaluwarsa.
    $booking->update(['payment_expired_at' => now()->subMinutes(5)]);

    $action = app(DokuReconciliationService::class)->reconcileTicket($booking->fresh());
    expect($action)->toBe(DokuReconciliationService::ACTION_CANCELLED_LAPSED)
        ->and($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(4);
});
