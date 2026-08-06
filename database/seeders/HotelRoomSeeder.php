<?php

namespace Database\Seeders;

use App\Models\HotelRoom;
use Illuminate\Database\Seeder;

class HotelRoomSeeder extends Seeder
{
    public function run(): void
    {
        HotelRoom::insert([
            ['room_type' => 'Standard Room', 'price_per_night' => 500000, 'quota' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['room_type' => 'Deluxe Room', 'price_per_night' => 850000, 'quota' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['room_type' => 'Suite Room', 'price_per_night' => 1200000, 'quota' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}