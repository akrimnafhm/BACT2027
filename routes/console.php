<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Batalkan otomatis booking pending yang melewati 24 jam / tiket kedaluwarsa
Schedule::command('bookings:cancel-expired')->everyMinute()->withoutOverlapping();

// Batalkan otomatis reservasi hotel pending yang melewati batas waktu pembayaran
Schedule::command('hotel-reservations:cancel-expired')->everyMinute()->withoutOverlapping();