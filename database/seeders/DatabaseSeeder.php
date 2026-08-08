<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Memanggil file seeder secara berurutan
        $this->call([
            UserSeeder::class,
            TicketSeeder::class,
            HotelRoomSeeder::class,
            NotificationTemplateSeeder::class,
        ]);
    }
}