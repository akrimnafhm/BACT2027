<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'channel',
        'label',
        'subject',
        'body',
        'include_qr',
        'is_active',
    ];

    protected $casts = [
        'include_qr' => 'boolean',
        'is_active'  => 'boolean',
    ];
}
