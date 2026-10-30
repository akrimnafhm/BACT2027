<?php

use App\Models\Ticket;
use App\Models\TicketBooking;
use App\Models\User;
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

function pcrUser(): User
{
    return tap(User::create([
        'name' => 'Peserta PCR',
        'email' => 'pcr-'.Str::random(6).'@test.com',
        'password' => bcrypt('password123'),
        'nik' => '3201010101'.random_int(100000, 999999),
        'gender' => 'Laki-laki',
        'phone_number' => '0812'.random_int(100000, 999999),
    ]), function (User $user) {
        $user->forceFill(['email_verified_at' => now()])->save();
    });
}

function pcrTicket(int $quota = 3): Ticket
{
    return Ticket::create([
        'ticket_name' => 'Basic',
        'ticket_category' => 'Basic',
        'price' => 100000,
        'quota' => $quota,
        'is_active' => true,
    ]);
}

function pcrPendingBooking(User $user, Ticket $ticket): TicketBooking
{
    return TicketBooking::create([
        'user_id' => $user->id,
        'ticket_id' => $ticket->id,
        'status' => 'pending',
        'source' => 'website',
        'ticket_name' => $ticket->ticket_name,
        'ticket_category' => $ticket->ticket_category,
        'amount' => $ticket->price,
        'invoice_number' => 'RC-PCR-'.Str::random(8),
        'payment_url' => null,
        'payment_expired_at' => null,
        'full_name' => $user->name,
        'name_with_title' => $user->name,
        'nik' => $user->nik,
        'profession' => 'Dokter',
        'whatsapp_number' => $user->phone_number,
        'gmail_account' => $user->email,
        'plataran_sehat_email' => $user->email,
        'institution_name' => 'RS Test',
        'institution_city' => 'Bandung',
        'institution_province' => 'Jawa Barat',
    ]);
}

it('menampilkan error gateway (bukan pesan pending) saat DOKU 503', function () {
    $user = pcrUser();
    $ticket = pcrTicket(quota: 3);
    $ticket->decrement('quota');
    $booking = pcrPendingBooking($user, $ticket);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response([], 503),
    ]);

    $this->actingAs($user)
        ->get(route('booking.form'))
        ->assertRedirect(route('booking.index'))
        ->assertSessionHas('error', 'Gagal memproses ke gerbang pembayaran DOKU. Silakan coba lagi beberapa saat lagi.');

    expect($booking->fresh()->status)->toBe('pending');
});

it('membatalkan pesanan hantu dan mengembalikan kuota saat DOKU 404', function () {
    $user = pcrUser();
    $ticket = pcrTicket(quota: 3);
    $ticket->decrement('quota');
    $booking = pcrPendingBooking($user, $ticket);

    Http::fake([
        'api-sandbox.doku.com/orders/v1/status/*' => Http::response([], 404),
    ]);

    $this->actingAs($user)
        ->get(route('booking.form'))
        ->assertOk();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->quota)->toBe(3);
});
