<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_name', 
        'ticket_category',
        'price', 
        'quota', 
        'start_date',
        'end_date'
    ];

    // Mengubah format tanggal menjadi instance Carbon agar mudah dimanipulasi
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function bookings()
    {
        return $this->hasMany(TicketBooking::class, 'ticket_id');
    }
}