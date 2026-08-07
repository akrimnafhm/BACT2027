<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ticket_id',
        'status',
        'ticket_name',
        'ticket_category',
        'amount',
        'invoice_number',
        'full_name',
        'name_with_title',
        'nik',
        'profession',
        'whatsapp_number',
        'gmail_account',
        'plataran_sehat_email',
        'institution_name',
        'institution_district',
        'institution_city',
        'institution_province',
        'checkin_token',
        'checked_in_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}