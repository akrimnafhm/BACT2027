<?php

use App\Models\Ticket;
use App\Models\TicketBooking;
use App\Models\User;
use App\Models\WaGroupLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

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

function waParticipantUser(string $email = 'peserta@test.com'): User
{
    return User::forceCreate([
        'name' => 'Peserta Test',
        'email' => $email,
        'password' => bcrypt('password123'),
    ]);
}

function paidTicketBookingFor(User $user, array $overrides = []): TicketBooking
{
    $ticket = Ticket::create([
        'ticket_name' => 'Regular',
        'ticket_category' => $overrides['ticket_category'] ?? 'Basic',
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
        'whatsapp_number' => $overrides['whatsapp_number'] ?? '081234567890',
        'gmail_account' => 'budi@test.com',
        'plataran_sehat_email' => '-',
        'institution_name' => 'RS Sehat',
        'institution_district' => 'Kec. Sukamaju',
        'institution_city' => 'Bandung',
        'institution_province' => 'Jawa Barat',
    ]);
}

function waGroupsCsv(string $body): UploadedFile
{
    return UploadedFile::fake()->createWithContent('anggota-grup.csv', $body);
}

it('menandai peserta yang nomornya cocok di CSV meski formatnya 62 berbanding 08', function () {
    $admin = waAdminUser();

    $cocok = waParticipantUser('cocok@test.com');
    $tidakCocok = waParticipantUser('tidak-cocok@test.com');

    WaGroupLink::create(['ticket_category' => 'Basic', 'wa_group_link' => 'https://chat.whatsapp.com/basic']);

    paidTicketBookingFor($cocok, ['whatsapp_number' => '081234567890']);
    paidTicketBookingFor($tidakCocok, ['whatsapp_number' => '081234000111']);

    // Di file ditulis dengan awalan 62 — harus tetap cocok dengan 08... di website
    $this->actingAs($admin)
        ->post(route('admin.participants.waScreen'), [
            'group' => 'Basic',
            'csv_file' => waGroupsCsv("name,phone\nAnggota A,+62 812-3456-7890\nAnggota Asing,628999888777\n"),
        ])
        ->assertRedirect();

    expect($cocok->fresh()->wa_joined_at)->not->toBeNull()
        ->and($cocok->fresh()->wa_joined_group)->toBe('Basic')
        ->and($tidakCocok->fresh()->wa_joined_at)->toBeNull()
        ->and(session('success'))->toContain('1 peserta baru ditandai')
        ->and(session('success'))->toContain('1 nomor di file tidak dikenali');
});

it('mencabut tanda milik grup yang sama saat scan ulang tanpa nomor tersebut', function () {
    $admin = waAdminUser();
    $user = waParticipantUser();

    WaGroupLink::create(['ticket_category' => 'Advanced', 'wa_group_link' => 'https://chat.whatsapp.com/adv']);
    paidTicketBookingFor($user, ['ticket_category' => 'Advanced', 'whatsapp_number' => '081222333444']);

    // Scan pertama: cocok -> bertanda
    $this->actingAs($admin)->post(route('admin.participants.waScreen'), [
        'group' => 'Advanced',
        'csv_file' => waGroupsCsv("phone\n6281222333444\n"),
    ])->assertRedirect();

    expect($user->fresh()->wa_joined_at)->not->toBeNull();

    // Scan ulang: nomor sudah keluar dari grup -> tanda dicabut
    $this->actingAs($admin)->post(route('admin.participants.waScreen'), [
        'group' => 'Advanced',
        'csv_file' => waGroupsCsv("phone\n628111111111\n"),
    ])->assertRedirect();

    expect($user->fresh()->wa_joined_at)->toBeNull()
        ->and($user->fresh()->wa_joined_group)->toBeNull();
});

it('tidak menghapus tanda dari grup lain ketika memindai grup berbeda', function () {
    $admin = waAdminUser();
    $user = waParticipantUser();

    WaGroupLink::create(['ticket_category' => 'Basic', 'wa_group_link' => 'https://chat.whatsapp.com/basic']);
    WaGroupLink::create(['ticket_category' => 'Advanced', 'wa_group_link' => 'https://chat.whatsapp.com/adv']);

    paidTicketBookingFor($user, ['whatsapp_number' => '081234567890']);

    // Bertanda lewat scan grup Basic
    $this->actingAs($admin)->post(route('admin.participants.waScreen'), [
        'group' => 'Basic',
        'csv_file' => waGroupsCsv("phone\n6281234567890\n"),
    ])->assertRedirect();

    // Scan grup Advanced tanpa nomor dia -> tanda Basic tetap utuh
    $this->actingAs($admin)->post(route('admin.participants.waScreen'), [
        'group' => 'Advanced',
        'csv_file' => waGroupsCsv("phone\n628777666555\n"),
    ])->assertRedirect();

    expect($user->fresh()->wa_joined_at)->not->toBeNull()
        ->and($user->fresh()->wa_joined_group)->toBe('Basic');
});

it('ikut menandai pembeli kategori combo saat memindai grup komponennya', function () {
    $admin = waAdminUser();
    $user = waParticipantUser();

    WaGroupLink::create(['ticket_category' => 'Basic', 'wa_group_link' => 'https://chat.whatsapp.com/basic']);

    // Pembeli Basic-Advanced adalah anggota grup Basic sekaligus Advanced
    paidTicketBookingFor($user, ['ticket_category' => 'Basic-Advanced', 'whatsapp_number' => '085512345678']);

    $this->actingAs($admin)->post(route('admin.participants.waScreen'), [
        'group' => 'Basic',
        'csv_file' => waGroupsCsv("phone\n6285512345678\n"),
    ])->assertRedirect();

    expect($user->fresh()->wa_joined_at)->not->toBeNull()
        ->and($user->fresh()->wa_joined_group)->toBe('Basic');
});

it('menolak grup tak terdaftar serta CSV tanpa kolom phone', function () {
    $admin = waAdminUser();

    // Grup tidak ada di tabel link grup WA
    $this->actingAs($admin)->post(route('admin.participants.waScreen'), [
        'group' => 'TidakAda',
        'csv_file' => waGroupsCsv("phone\n6281234567890\n"),
    ])->assertRedirect()->assertSessionHas('error');

    // Kolom "phone" tidak ada pada header
    WaGroupLink::create(['ticket_category' => 'Basic', 'wa_group_link' => 'https://chat.whatsapp.com/basic']);
    $this->actingAs($admin)->post(route('admin.participants.waScreen'), [
        'group' => 'Basic',
        'csv_file' => waGroupsCsv("nomor_hp\n6281234567890\n"),
    ])->assertRedirect()->assertSessionHas('error');

    // Validasi wajib: file & grup kosong
    $this->actingAs($admin)->post(route('admin.participants.waScreen'), [])
        ->assertSessionHasErrors(['group', 'csv_file']);
});

it('tetap mendukung konfirmasi manual WA oleh admin', function () {
    $admin = waAdminUser();
    $user = waParticipantUser();

    WaGroupLink::create(['ticket_category' => 'Basic', 'wa_group_link' => 'https://chat.whatsapp.com/basic']);
    $booking = paidTicketBookingFor($user);

    // Konfirmasi manual -> tercatat sudah join dengan sumber "manual"
    $this->actingAs($admin)
        ->post(route('admin.participants.waToggle', $booking->id))
        ->assertRedirect();

    expect($user->fresh()->wa_joined_at)->not->toBeNull()
        ->and($user->fresh()->wa_joined_group)->toBe('manual');

    // Tekan lagi -> dikosongkan kembali
    $this->actingAs($admin)
        ->post(route('admin.participants.waToggle', $booking->id))
        ->assertRedirect();

    expect($user->fresh()->wa_joined_at)->toBeNull()
        ->and($user->fresh()->wa_joined_group)->toBeNull();
});
