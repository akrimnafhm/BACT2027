<?php

namespace Database\Seeders;

use App\Models\HotelRoom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class HotelRoomSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Matikan pengecekan relasi (foreign key) sementara
        Schema::disableForeignKeyConstraints();

        // 2. Kosongkan tabel dengan aman
        HotelRoom::truncate();

        // 3. Nyalakan kembali pengecekan relasi
        Schema::enableForeignKeyConstraints();

        // 4. Masukkan data kamar baru
        HotelRoom::insert([
            [
                'room_type'       => 'Deluxe King', 
                'price_per_night' => 1850000, 
                'quota'           => 10,
                'is_active'       => 1,
                'created_at'      => now(), 
                'updated_at'      => now()
            ],
            [
                'room_type'       => 'Deluxe Twin', 
                'price_per_night' => 1850000, 
                'quota'           => 10,
                'is_active'       => 1,
                'created_at'      => now(), 
                'updated_at'      => now()
            ],
        ]);
    }
}