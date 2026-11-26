<?php

use App\Models\Ticket;
use App\Models\TicketBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function termsAgreementUser(): User
{
    return tap(User::create([
        'name' => 'Peserta S&K',
        'email' => 'terms-'.Str::random(6).'@test.com',
        'password' => bcrypt('password123'),
        'nik' => '3201010101'.random_int(100000, 999999),
        'gender' => 'Laki-laki',
        'phone_number' => '0812'.random_int(100000, 999999),
    ]), function (User $user) {
        $user->forceFill(['email_verified_at' => now()])->save();
    });
}

function termsAgreementTicket(): Ticket
{
    return Ticket::create([
        'ticket_name' => 'Basic',
        'ticket_category' => 'Basic',
        'price' => 100000,
        'quota' => 3,
    ]);
}

function termsAgreementPayload(Ticket $ticket): array
{
    return [
        'ticket_id' => $ticket->id,
        'profession' => 'Dokter',
        'plataran_sehat_email' => 'peserta@test.com',
        'institution_name' => 'RS Test',
        'institution_city' => 'Bandung',
        'institution_province' => 'Jawa Barat',
    ];
}

test('form booking menampilkan checkbox persetujuan dan popup syarat ketentuan', function () {
    $this->actingAs(termsAgreementUser())
        ->get(route('booking.form'))
        ->assertOk()
        ->assertSee('name="agree_terms"', false)
        ->assertSee('id="submit-booking" disabled', false)
        ->assertSee('id="open-terms"', false)
        ->assertSee('id="terms-modal"', false)
        ->assertSeeText('Lihat syarat & ketentuan')
        ->assertSeeText('SYARAT DAN KETENTUAN PESERTA')
        ->assertSeeText('BASIC–ADVANCED COURSE IN TRANSFUSION (BACT) 2027')
        ->assertSeeText('1. Kelengkapan Data Peserta')
        ->assertSeeText('2. Perubahan Paket Kepesertaan')
        ->assertSeeText('3. Pembatalan Kepesertaan dan Ketentuan Pengembalian Dana (Refund)')
        ->assertSeeText('Pembatalan hingga tanggal 5 Januari 2027: Pengembalian dana sebesar 60%')
        ->assertSeeText('Pembatalan setelah tanggal 5 Januari 2027: Pengembalian dana sebesar 25%')
        ->assertSeeText('Pengembalian dana akan diproses 7 hari setelah acara berakhir.')
        ->assertSeeText('Dengan melakukan pendaftaran, peserta dianggap telah membaca, memahami, dan menyetujui');
});

test('proses booking menolak pesanan tanpa persetujuan syarat ketentuan', function () {
    $user = termsAgreementUser();
    $ticket = termsAgreementTicket();

    $this->actingAs($user)
        ->post(route('booking.process'), termsAgreementPayload($ticket))
        ->assertSessionHasErrors(['agree_terms']);

    $this->assertDatabaseMissing('ticket_bookings', ['user_id' => $user->id]);
});

test('proses booking berhasil setelah syarat ketentuan disetujui', function () {
    $user = termsAgreementUser();
    $ticket = termsAgreementTicket();

    $this->actingAs($user)
        ->post(route('booking.process'), termsAgreementPayload($ticket) + ['agree_terms' => '1'])
        ->assertRedirect();

    $this->assertDatabaseHas('ticket_bookings', [
        'user_id' => $user->id,
        'ticket_id' => $ticket->id,
        'status' => 'pending',
    ]);
    expect(TicketBooking::where('user_id', $user->id)->count())->toBe(1);
});
