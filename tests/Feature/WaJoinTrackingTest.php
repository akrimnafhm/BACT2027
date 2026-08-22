<?php

use App\Models\Ticket;
use App\Models\TicketBooking;
use App\Models\User;
use App\Models\WaGroupLink;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function waAdminUser(): User
{
    return User::forceCreate([
        'name' => 'Admin Test',
        'email' => 'admin@test.com',
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);
}

function paidTicketBookingFor(User $user): TicketBooking
{
    $ticket = Ticket::create([
        'ticket_name' => 'Regular',
        'ticket_category' => 'Basic',
        'price' => 100000,
        'quota' => 10,
    ]);

    return TicketBooking::create([
        'user_id' => $user->id,
        'ticket_id' => $ticket->id,
        'ticket_name' => $ticket->ticket_name,
        'ticket_category' => $ticket->ticket_category,
        'amount' => $ticket->price,
        'invoice_number' => 'INV-TEST-'.uniqid(),
        'checkin_token' => uniqid('chk_'),
        'status' => 'paid',
        'source' => 'website',
        'paid_at' => now(),
        'full_name' => 'Budi Santoso',
        'name_with_title' => 'dr. Budi Santoso, Sp.A',
        'nik' => '3201234567890001',
        'profession' => 'Dokter',
        'whatsapp_number' => '081234567890',
        'gmail_account' => 'budi@test.com',
        'plataran_sehat_email' => '-',
        'institution_name' => 'RS Sehat',
        'institution_district' => 'Kec. Sukamaju',
        'institution_city' => 'Bandung',
        'institution_province' => 'Jawa Barat',
    ]);
}

it('menandai user sudah join saat klik link WA terpantau dan mengarahkan ke grup asli', function () {
    $user = User::forceCreate([
        'name' => 'Peserta Test',
        'email' => 'peserta@test.com',
        'password' => bcrypt('password123'),
    ]);

    WaGroupLink::create([
        'ticket_category' => 'Basic',
        'wa_group_link' => 'https://chat.whatsapp.com/grup-basic',
    ]);

    $response = $this->actingAs($user)->get(route('booking.wa-join', ['category' => 'Basic']));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toBe('https://chat.whatsapp.com/grup-basic')
        ->and($user->fresh()->wa_joined_at)->not->toBeNull();
});

it('tidak menimpa waktu join pertama bila klik tombol lagi', function () {
    $user = User::forceCreate([
        'name' => 'Peserta Test',
        'email' => 'peserta2@test.com',
        'password' => bcrypt('password123'),
    ]);

    WaGroupLink::create([
        'ticket_category' => 'Advanced',
        'wa_group_link' => 'https://chat.whatsapp.com/grup-advanced',
    ]);

    $first = now()->subMinutes(10);
    $user->forceFill(['wa_joined_at' => $first])->save();

    $this->actingAs($user)->get(route('booking.wa-join', ['category' => 'Advanced']));

    // Waktu tetap milik klik pertama
    expect($user->fresh()->wa_joined_at->getTimestamp())->toBe($first->getTimestamp());
});

it('tidak menandai apa pun bila kategori belum punya link grup', function () {
    $user = User::forceCreate([
        'name' => 'Peserta Test',
        'email' => 'peserta3@test.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->actingAs($user)->get(route('booking.wa-join', ['category' => 'TidakAda']));

    $response->assertRedirect(route('booking.index'));
    expect(session('error'))->not->toBeNull()
        ->and($user->fresh()->wa_joined_at)->toBeNull();
});

it('admin dapat konfirmasi manual dan membatalkan status join WA peserta', function () {
    $admin = waAdminUser();

    $user = User::forceCreate([
        'name' => 'Peserta Test',
        'email' => 'peserta4@test.com',
        'password' => bcrypt('password123'),
    ]);

    $booking = paidTicketBookingFor($user);

    // Konfirmasi manual -> tercatat sudah join
    $this->actingAs($admin)
        ->post(route('admin.participants.waToggle', $booking->id))
        ->assertRedirect();

    expect($user->fresh()->wa_joined_at)->not->toBeNull();

    // Tekan lagi -> dikosongkan kembali
    $this->actingAs($admin)
        ->post(route('admin.participants.waToggle', $booking->id))
        ->assertRedirect();

    expect($user->fresh()->wa_joined_at)->toBeNull();
});
