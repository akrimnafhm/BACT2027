<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelRoom extends Model
{
    use HasFactory;

    // Mengizinkan mass-assignment untuk semua kolom seperti kode awalmu
    protected $guarded = [];

    // Mengubah data JSON dari database menjadi Array secara otomatis
    protected $casts = [
        'photos' => 'array',
    ];
}