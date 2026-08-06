<?php

namespace Database\Seeders;

use App\Models\Ticket;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        
        // Batas tanggal Early Bird: hingga 31 Oktober 2026
        $earlyBirdEnd = Carbon::create(2026, 10, 31, 23, 59, 59);
        
        // Rentang tanggal Regular: 1 November 2026 - 19 Januari 2027
        $regularStart = Carbon::create(2026, 11, 1, 0, 0, 0); 
        $regularEnd   = Carbon::create(2027, 1, 19, 23, 59, 59);

        // ==========================================
        // 1. TIKET EARLY BIRD (Hingga 31 Oktober 2026)
        // ==========================================
        Ticket::insert([
            [
                'ticket_name'     => 'Early Bird', 
                'ticket_category' => 'Basic', 
                'price'           => 850000, 
                'quota'           => 50, 
                'start_date'      => $now, 
                'end_date'        => $earlyBirdEnd, 
                'created_at'      => now(), 
                'updated_at'      => now()
            ],
            [
                'ticket_name'     => 'Early Bird', 
                'ticket_category' => 'Advance', 
                'price'           => 1850000, 
                'quota'           => 50, 
                'start_date'      => $now, 
                'end_date'        => $earlyBirdEnd, 
                'created_at'      => now(), 
                'updated_at'      => now()
            ],
            [
                'ticket_name'     => 'Early Bird', 
                'ticket_category' => 'Basic-Advance', 
                'price'           => 2200000, 
                'quota'           => 50, 
                'start_date'      => $now, 
                'end_date'        => $earlyBirdEnd, 
                'created_at'      => now(), 
                'updated_at'      => now()
            ],
            [
                'ticket_name'     => 'Early Bird', 
                'ticket_category' => 'Online', 
                'price'           => 500000, 
                'quota'           => 50, 
                'start_date'      => $now, 
                'end_date'        => $earlyBirdEnd, 
                'created_at'      => now(), 
                'updated_at'      => now()
            ],
            [
                'ticket_name'     => 'Early Bird', 
                'ticket_category' => 'Workshop', 
                'price'           => 800000, 
                'quota'           => 50, 
                'start_date'      => $now, 
                'end_date'        => $earlyBirdEnd, 
                'created_at'      => now(), 
                'updated_at'      => now()
            ],
            [
                'ticket_name'     => 'Early Bird', 
                'ticket_category' => 'Basic-Advance + Workshop', 
                'price'           => 2900000, 
                'quota'           => 50, 
                'start_date'      => $now, 
                'end_date'        => $earlyBirdEnd, 
                'created_at'      => now(), 
                'updated_at'      => now()
            ],
        ]);

        // ==========================================
        // 2. TIKET REGULAR (1 November 2026 - 19 Januari 2027)
        // ==========================================
        Ticket::insert([
            [
                'ticket_name'     => 'Regular', 
                'ticket_category' => 'Basic', 
                'price'           => 1100000, 
                'quota'           => 150, 
                'start_date'      => $regularStart, 
                'end_date'        => $regularEnd, 
                'created_at'      => now(), 
                'updated_at'      => now()
            ],
            [
                'ticket_name'     => 'Regular', 
                'ticket_category' => 'Advance', 
                'price'           => 2100000, 
                'quota'           => 150, 
                'start_date'      => $regularStart, 
                'end_date'        => $regularEnd, 
                'created_at'      => now(), 
                'updated_at'      => now()
            ],
            [
                'ticket_name'     => 'Regular', 
                'ticket_category' => 'Basic-Advance', 
                'price'           => 2700000, 
                'quota'           => 150, 
                'start_date'      => $regularStart, 
                'end_date'        => $regularEnd, 
                'created_at'      => now(), 
                'updated_at'      => now()
            ],
            [
                'ticket_name'     => 'Regular', 
                'ticket_category' => 'Online', 
                'price'           => 750000, 
                'quota'           => 150, 
                'start_date'      => $regularStart, 
                'end_date'        => $regularEnd, 
                'created_at'      => now(), 
                'updated_at'      => now()
            ],
            [
                'ticket_name'     => 'Regular', 
                'ticket_category' => 'Workshop', 
                'price'           => 1000000, 
                'quota'           => 150, 
                'start_date'      => $regularStart, 
                'end_date'        => $regularEnd, 
                'created_at'      => now(), 
                'updated_at'      => now()
            ],
            [
                'ticket_name'     => 'Regular', 
                'ticket_category' => 'Basic-Advance + Workshop', 
                'price'           => 3400000, 
                'quota'           => 150, 
                'start_date'      => $regularStart, 
                'end_date'        => $regularEnd, 
                'created_at'      => now(), 
                'updated_at'      => now()
            ],
        ]);
    }
}