<?php

use App\Models\HotelReservation;
use App\Models\HotelRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function hotelUser(): User
{
    // email_verified_at tidak masuk $fillable, jadi diisi lewat forceFill
    return tap(User::create([
        'name' => 'Peserta Test',
        'email' => 'peserta@test.com',
        'password' => bcrypt('password123'),
        'nik' => '3201010101010001',
        'gender' => 'Laki-laki',
        'phone_number' => '081234567890',
    ]), function (User $user) {
        $user->forceFill(['email_verified_at' => now()])->save();
    });
}

function hotelRoom(int $quota = 5): HotelRoom
{
    return HotelRoom::create([
        'room_type' => 'Deluxe King',
        'price_per_night' => 500000,
        'quota' => $quota,
        'is_active' => true,
    ]);
}

it('menyimpan reservasi dengan jumlah kamar dan mengurangi kuota proporsional', function () {
    $user = hotelUser();
    $room = hotelRoom(quota: 5);

    $this->actingAs($user)->post(route('hotels.store', $room->id), [
        'check_in' => '2027-01-18',
        'check_out' => '2027-01-20',
        'quantity' => 2,
    ]);

    expect(HotelReservation::count())->toBe(1)
        ->and($room->fresh()->quota)->toBe(3);

    $reservation = HotelReservation::first();
    expect((int) $reservation->quantity)->toBe(2)
        ->and((int) $reservation->total_nights)->toBe(2)
        ->and((float) $reservation->total_price)->toBe(2000000.0);
});

it('menolak reservasi baru saat masih ada pembayaran pending', function () {
    $user = hotelUser();
    $room = hotelRoom();

    $this->actingAs($user)->post(route('hotels.store', $room->id), [
        'check_in' => '2027-01-18',
        'check_out' => '2027-01-19',
        'quantity' => 1,
    ])->assertRedirect(route('hotels.checkout', 1));

    $response = $this->actingAs($user)->post(route('hotels.store', $room->id), [
        'check_in' => '2027-01-19',
        'check_out' => '2027-01-20',
        'quantity' => 1,
    ]);

    expect(HotelReservation::count())->toBe(1);
});

it('mengembalikan kuota sebanyak jumlah kamar saat dibatalkan user', function () {
    $user = hotelUser();
    $room = hotelRoom(quota: 3);

    $this->actingAs($user)->post(route('hotels.store', $room->id), [
        'check_in' => '2027-01-18',
        'check_out' => '2027-01-21',
        'quantity' => 2,
    ]);

    expect($room->fresh()->quota)->toBe(1);

    $reservation = HotelReservation::first();
    $this->actingAs($user)->post(route('hotels.cancel', $reservation->id));

    expect($room->fresh()->quota)->toBe(3)
        ->and($reservation->fresh()->status)->toBe('cancelled');
});

it('membolehkan beberapa reservasi lunas termasuk tipe kamar yang sama', function () {
    $user = hotelUser();
    $room = hotelRoom(quota: 10);

    // Reservasi pertama langsung dilunaskan
    $first = HotelReservation::create([
        'booking_code' => 'HTL-20270118-AAAA',
        'user_id' => $user->id,
        'hotel_room_id' => $room->id,
        'check_in' => '2027-01-18',
        'check_out' => '2027-01-19',
        'total_nights' => 1,
        'quantity' => 1,
        'total_price' => 500000,
        'status' => 'paid',
        'guest_name' => $user->name,
        'guest_nik' => $user->nik,
        'guest_phone' => $user->phone_number,
        'guest_email' => $user->email,
    ]);
    $room->decrement('quota');

    // Reservasi kedua, tipe kamar SAMA, harusnya diizinkan
    $this->actingAs($user)->post(route('hotels.store', $room->id), [
        'check_in' => '2027-01-19',
        'check_out' => '2027-01-20',
        'quantity' => 1,
    ])->assertRedirect(route('hotels.checkout', HotelReservation::latest('id')->first()->id));

    expect(HotelReservation::where('status', 'paid')->count())->toBe(1)
        ->and(HotelReservation::count())->toBe(2);

    // Halaman /hotel hanya menampilkan daftar reservasi (tanpa katalog)
    $this->actingAs($user)->get(route('hotels.index'))
        ->assertOk()
        ->assertSee($first->booking_code)
        ->assertSee('Reservasi Hotel Lagi')
        ->assertDontSee('Pesan Kamar Ini');

    // Katalog berada di halaman terpisah
    $this->get(route('hotels.catalog'))
        ->assertOk()
        ->assertSee('Pesan Kamar Ini')
        ->assertDontSee($first->booking_code);
});

it('menampilkan empty state ketika peserta belum pernah reservasi hotel', function () {
    $user = hotelUser();
    hotelRoom();

    $this->actingAs($user)->get(route('hotels.index'))
        ->assertOk()
        ->assertSee('Anda belum memesan hotel sama sekali')
        ->assertSee('Lihat Katalog & Pesan Kamar');
});

it('menolak jumlah kamar melebihi sisa kuota', function () {
    $user = hotelUser();
    $room = hotelRoom(quota: 1);

    $this->actingAs($user)->post(route('hotels.store', $room->id), [
        'check_in' => '2027-01-18',
        'check_out' => '2027-01-19',
        'quantity' => 2,
    ]);

    expect(HotelReservation::count())->toBe(0)
        ->and($room->fresh()->quota)->toBe(1);
});
