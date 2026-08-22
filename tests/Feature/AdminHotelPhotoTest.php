<?php

use App\Models\HotelRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function adminUser(): User
{
    return User::forceCreate([
        'name' => 'Admin Test',
        'email' => 'admin@test.com',
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);
}

it('mengelola foto kamar saat edit: hapus per foto dan tambah foto baru', function () {
    Storage::fake('public');

    $admin = adminUser();
    // Buat kamar dengan 2 foto lama (tanpa lewat upload supaya path bisa dikontrol)
    $oldA = 'hotels/old-a.jpg';
    $oldB = 'hotels/old-b.jpg';
    Storage::disk('public')->put($oldA, 'a');
    Storage::disk('public')->put($oldB, 'b');

    $room = HotelRoom::create([
        'room_type' => 'Deluxe King',
        'price_per_night' => 500000,
        'quota' => 5,
        'is_active' => true,
        'photos' => [$oldA, $oldB],
    ]);

    $newPhoto = UploadedFile::fake()->image('new.jpg', 100, 100);

    $this->actingAs($admin)->put(route('admin.hotels.update', $room->id), [
        'room_type' => 'Deluxe King',
        'price_per_night' => 500000,
        'quota' => 0,
        'description' => null,
        'removed_photos' => [$oldA],
        'photos' => [$newPhoto],
    ]);

    $room->refresh();
    $photos = $room->photos;

    // Foto lama A hilang, B tetap, foto baru ditambahkan
    expect($photos)->not->toContain($oldA)
        ->and($photos)->toContain($oldB)
        ->and(count($photos))->toBe(2)
        ->and(Storage::disk('public')->exists($oldA))->toBeFalse()
        ->and(Storage::disk('public')->exists($oldB))->toBeTrue()
        ->and((int) $room->quota)->toBe(5);

    // Foto lama yang tersisa masih ada di storage dan terdaftar di DB
    foreach ($photos as $path) {
        if ($path !== $oldB) {
            expect(Storage::disk('public')->exists($path))->toBeTrue();
        }
    }
});

it('menolak total foto lebih dari 5 saat mengedit kamar', function () {
    Storage::fake('public');
    $admin = adminUser();

    $existing = [];
    for ($i = 1; $i <= 4; $i++) {
        $path = "hotels/keep-{$i}.jpg";
        Storage::disk('public')->put($path, 'x');
        $existing[] = $path;
    }

    $room = HotelRoom::create([
        'room_type' => 'Suite',
        'price_per_night' => 900000,
        'quota' => 3,
        'is_active' => true,
        'photos' => $existing,
    ]);

    // 4 foto lama dipertahankan + 2 foto baru = 6 -> harus ditolak
    $response = $this->actingAs($admin)->put(route('admin.hotels.update', $room->id), [
        'room_type' => 'Suite',
        'price_per_night' => 900000,
        'quota' => 0,
        'description' => null,
        'photos' => [
            UploadedFile::fake()->image('n1.jpg'),
            UploadedFile::fake()->image('n2.jpg'),
        ],
    ]);

    $response->assertSessionHasErrors('photos');

    // Data tidak berubah
    $room->refresh();
    expect(count($room->photos))->toBe(4);
});

it('menerapkan kuota sebagai selisih dan menolak hasil negatif', function () {
    Storage::fake('public');
    $admin = adminUser();

    $room = HotelRoom::create([
        'room_type' => 'Deluxe Twin',
        'price_per_night' => 750000,
        'quota' => 5,
        'is_active' => true,
    ]);

    // Tambah 3 kamar -> 8
    $this->actingAs($admin)->put(route('admin.hotels.update', $room->id), [
        'room_type' => 'Deluxe Twin',
        'price_per_night' => 750000,
        'quota' => 3,
        'description' => null,
    ]);
    expect((int) $room->fresh()->quota)->toBe(8);

    // Kurangi 2 kamar -> 6
    $this->actingAs($admin)->put(route('admin.hotels.update', $room->id), [
        'room_type' => 'Deluxe Twin',
        'price_per_night' => 750000,
        'quota' => -2,
        'description' => null,
    ]);
    expect((int) $room->fresh()->quota)->toBe(6);

    // Kosong = tidak berubah
    $this->actingAs($admin)->put(route('admin.hotels.update', $room->id), [
        'room_type' => 'Deluxe Twin',
        'price_per_night' => 750000,
        'description' => null,
    ]);
    expect((int) $room->fresh()->quota)->toBe(6);

    // Hasil negatif (-10 dari 6) -> ditolak, kuota tetap
    $this->actingAs($admin)->put(route('admin.hotels.update', $room->id), [
        'room_type' => 'Deluxe Twin',
        'price_per_night' => 750000,
        'quota' => -10,
        'description' => null,
    ])->assertSessionHasErrors('quota');

    expect((int) $room->fresh()->quota)->toBe(6);
});
