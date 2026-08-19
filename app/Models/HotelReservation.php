<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelReservation extends Model
{
    use HasFactory;

    /**
     * Menggunakan $fillable secara eksplisit agar aman saat updateOrCreate()
     */
    protected $fillable = [
        'booking_code',
        'user_id',
        'hotel_room_id',
        'check_in',
        'check_out',
        'total_nights',
        'total_price',
        'guest_name',
        'guest_nik',
        'guest_phone',
        'guest_email',
        'status',
        'invoice_number',
        'payment_url',
        'payment_expired_at',
        'payment_method',
        'notified_at',
        'cancelled_at',
    ];

    /**
     * Tipe data konversi otomatis (casting)
     */
    protected $casts = [
        'check_in'     => 'date',
        'check_out'    => 'date',
        'total_nights' => 'integer',
        'total_price'  => 'decimal:2',
        'notified_at'  => 'datetime',
        'payment_expired_at' => 'datetime',
        'cancelled_at'  => 'datetime',
    ];

    /**
     * Relasi ke Model User (Pemesan)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Model HotelRoom (Tipe Kamar)
     */
    public function hotelRoom()
    {
        return $this->belongsTo(HotelRoom::class);
    }
}