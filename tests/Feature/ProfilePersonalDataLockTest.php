<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function profileLockUser(array $overrides = []): User
{
    return User::forceCreate(array_merge([
        'name' => 'Nama Awal',
        'email' => 'profil-'.uniqid().'@test.com',
        'password' => bcrypt('password123'),
        'role' => 'peserta',
        'phone_number' => '081234567890',
        'nik' => '3201010101010001',
        'gender' => 'Laki-laki',
        'email_verified_at' => now(),
    ], $overrides));
}

it('mengunci data pribadi setelah simpan pertama', function () {
    $user = profileLockUser([
        'nik' => null,
        'gender' => null,
    ]);

    $this->actingAs($user)
        ->post(route('profile.update'), [
            'name' => 'Nama Sesuai KTP',
            'nik' => '3201010101010002',
            'gender' => 'Laki-laki',
            'phone_number' => '081234567890',
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->name)->toBe('Nama Sesuai KTP')
        ->and($user->nik)->toBe('3201010101010002')
        ->and($user->gender)->toBe('Laki-laki')
        ->and($user->personal_data_locked_at)->not->toBeNull();
});

it('menolak perubahan nama, nik, dan jenis kelamin saat sudah terkunci', function () {
    $user = profileLockUser([
        'nik' => '3201010101010003',
        'gender' => 'Laki-laki',
        'personal_data_locked_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('profile.update'), [
            'name' => 'Nama Orang Lain',
            'nik' => '3201010101019999',
            'gender' => 'Perempuan',
            'phone_number' => '081298765432',
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->name)->toBe('Nama Awal')
        ->and($user->nik)->toBe('3201010101010003')
        ->and($user->gender)->toBe('Laki-laki')
        ->and($user->phone_number)->toBe('081298765432');
});

it('menampilkan pop-up peringatan hanya saat data pribadi belum terkunci', function () {
    $unlocked = profileLockUser(['nik' => null, 'gender' => null]);
    $locked = profileLockUser([
        'nik' => '3201010101010004',
        'personal_data_locked_at' => now(),
    ]);

    $this->actingAs($unlocked)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('personal-data-lock-modal');

    $this->actingAs($locked)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Terkunci Permanen')
        ->assertDontSee('personal-data-lock-modal');
});
