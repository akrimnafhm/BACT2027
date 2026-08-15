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
        'source',
        'notes',
        'notes_updated_at',
        'cancelled_at',
        'confirmed_at',
        'deleted_at',
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
        'notified_at',
    ];

    protected $casts = [
        'checked_in_at'    => 'datetime',
        'notified_at'      => 'datetime',
        'notes_updated_at' => 'datetime',
        'cancelled_at'     => 'datetime',
        'confirmed_at'     => 'datetime',
        'deleted_at'       => 'datetime',
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