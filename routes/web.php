<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\HotelBookingController;
use App\Http\Controllers\ContentController;
use App\Http\Middleware\IsAdmin;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

// ==========================================
// RUTE PUBLIK (Bisa diakses siapa saja)
// ==========================================

// Halaman Utama (Landing Page dengan data Info, Pembicara, Jadwal, Galeri, Sponsor)
Route::get('/', [HomeController::class, 'index'])->name('home');

// Halaman Bridge Booking (Diletakkan di luar middleware auth agar bisa mengecek status login guest)
Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');
Route::get('/booking/form', [BookingController::class, 'form'])->name('booking.form');
Route::get('/hotel', [HotelBookingController::class, 'index'])->name('hotels.index');


// ==========================================
// RUTE GUEST (Hanya untuk yang belum login)
// ==========================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'processLogin']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'processRegister']);
});


// ==========================================
// RUTE USER (Hanya untuk yang sudah login)
// ==========================================
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Manajemen Profil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Verifikasi Email
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('success', 'Link verifikasi telah dikirim ke email Anda! Cek folder Log.');
    })->middleware('throttle:6,1')->name('verification.send');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect('/profile')->with('success', 'Email berhasil diverifikasi!');
    })->middleware('signed')->name('verification.verify');

    // Verifikasi OTP HP
    Route::post('/phone/send-otp', [ProfileController::class, 'sendOtp'])->name('phone.sendOtp');
    Route::post('/phone/verify-otp', [ProfileController::class, 'verifyOtp'])->name('phone.verifyOtp');

    // Proses Pengiriman Form Booking (Menyimpan data dengan status pending)
    Route::post('/booking/process', [BookingController::class, 'process'])->name('booking.process');

    // Return URL dari DOKU agar status booking bisa disinkronkan saat user kembali ke site
    Route::get('/booking/return/{booking}', [BookingController::class, 'paymentReturn'])->name('booking.return');

    // Halaman Checkout & Pembayaran Midtrans
    Route::get('/checkout/{id}', [BookingController::class, 'checkout'])->name('checkout');

    // AKOMODASI HOTEL (Katalog & Pemesanan Peserta)
    Route::get('/hotel/book/{id}', [HotelBookingController::class, 'create'])->name('hotels.book');
    Route::post('/hotel/book/{id}', [HotelBookingController::class, 'store'])->name('hotels.store');
});


// ==========================================
// RUTE KHUSUS ADMIN (Dilindungi Middleware)
// ==========================================
Route::middleware(['auth', IsAdmin::class])->prefix('admin')->group(function () {

    // 0. Redirect otomatis dari URL /admin ke halaman Kuota & Harga
    Route::get('/', function () {
        return redirect('/admin/tickets');
    });

    // 1. Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // =========================================================
    // 2. KUOTA & HARGA (Master Tiket Simposium & Kamar Hotel)
    // =========================================================
    // Tiket Simposium
    Route::get('/tickets', [AdminController::class, 'tickets'])->name('admin.tickets.index');
    Route::post('/tickets', [AdminController::class, 'storeTicket'])->name('admin.tickets.store');
    Route::put('/tickets/{id}', [AdminController::class, 'updateTicket'])->name('admin.tickets.update');
    Route::delete('/tickets/{id}', [AdminController::class, 'destroyTicket'])->name('admin.tickets.destroy');
    Route::post('/tickets/{id}/toggle-status', [AdminController::class, 'toggleTicketStatus'])->name('admin.tickets.toggle');

    // Kamar Hotel (CRUD & Toggle dikelola di dalam halaman Kuota & Harga / AdminController)
    Route::post('/tickets/hotels', [AdminController::class, 'storeHotel'])->name('admin.hotels.store');
    Route::put('/tickets/hotels/{id}', [AdminController::class, 'updateHotel'])->name('admin.hotels.update');
    Route::delete('/tickets/hotels/{id}', [AdminController::class, 'destroyHotel'])->name('admin.hotels.destroy');
    Route::post('/tickets/hotels/{id}/toggle-status', [AdminController::class, 'toggleHotelStatus'])->name('admin.hotels.toggle');

    // =========================================================
    // 3. TIKET PESERTA SIMPOSIUM
    // =========================================================
    Route::get('/participants', [AdminController::class, 'participants'])->name('admin.participants');
    Route::get('/participants/export', [AdminController::class, 'exportParticipants'])->name('admin.participants.export');
    Route::post('/participants/store-manual', [AdminController::class, 'storeParticipantManual'])->name('admin.participants.storeManual');
    Route::post('/participants/{id}/update-status', [AdminController::class, 'updateParticipantStatus'])->name('admin.participants.updateStatus');

    // =========================================================
    // 4. TIKET HOTEL (Data & Status Riwayat Reservasi Peserta)
    // =========================================================
    Route::get('/hotels', [HotelController::class, 'index'])->name('admin.hotels.index');
    Route::post('/hotels/reservations/{id}/status', [HotelController::class, 'updateReservationStatus'])->name('admin.hotels.reservations.status');

    // =========================================================
    // 5. KARYA LOMBA (Placeholder)
    // =========================================================
    Route::get('/submissions', function () {
        return 'Halaman Data Karya Lomba (Coming Soon)';
    })->name('admin.submissions.index');

    // =========================================================
    // 6. KELOLA KONTEN CMS
    // =========================================================
    Route::get('/content', [ContentController::class, 'index'])->name('admin.content.index');

    // --- RUTE CRUD TAB 1: INFO & PENGUMUMAN ---
    Route::post('/content/announcements', [ContentController::class, 'storeAnnouncement'])->name('admin.announcements.store');
    Route::put('/content/announcements/{id}', [ContentController::class, 'updateAnnouncement'])->name('admin.announcements.update');
    Route::delete('/content/announcements/{id}', [ContentController::class, 'destroyAnnouncement'])->name('admin.announcements.destroy');
    Route::post('/content/announcements/{id}/toggle', [ContentController::class, 'toggleAnnouncementStatus'])->name('admin.announcements.toggle');

    // --- RUTE CRUD TAB 2: PEMBICARA SIMPOSIUM ---
    Route::post('/content/speakers', [ContentController::class, 'storeSpeaker'])->name('admin.speakers.store');
    Route::put('/content/speakers/{id}', [ContentController::class, 'updateSpeaker'])->name('admin.speakers.update');
    Route::delete('/content/speakers/{id}', [ContentController::class, 'destroySpeaker'])->name('admin.speakers.destroy');
    Route::post('/content/speakers/{id}/move-up', [ContentController::class, 'moveUpSpeaker'])->name('admin.speakers.move-up');
    Route::post('/content/speakers/{id}/move-down', [ContentController::class, 'moveDownSpeaker'])->name('admin.speakers.move-down');

    // --- RUTE CRUD TAB 3: JADWAL ACARA ---
    Route::post('/content/schedules', [ContentController::class, 'storeSchedule'])->name('admin.schedules.store');
    Route::put('/content/schedules/{id}', [ContentController::class, 'updateSchedule'])->name('admin.schedules.update');
    Route::delete('/content/schedules/{id}', [ContentController::class, 'destroySchedule'])->name('admin.schedules.destroy');

    // --- RUTE CRUD TAB 4: GALERI FOTO ---
    Route::post('/content/galleries', [ContentController::class, 'storeGallery'])->name('admin.galleries.store');
    Route::delete('/content/galleries/{id}', [ContentController::class, 'destroyGallery'])->name('admin.galleries.destroy');

    // --- RUTE CRUD TAB 5: SPONSOR & MITRA ---
    Route::post('/content/sponsors', [ContentController::class, 'storeSponsor'])->name('admin.sponsors.store');
    Route::put('/content/sponsors/{id}', [ContentController::class, 'updateSponsor'])->name('admin.sponsors.update');
    Route::delete('/content/sponsors/{id}', [ContentController::class, 'destroySponsor'])->name('admin.sponsors.destroy');

    // =========================================================
    // 7. BROADCAST WA & 8. QR CHECK-IN (Placeholder)
    // =========================================================
    Route::get('/broadcast', function () {
        return 'Halaman Broadcast WA (Coming Soon)';
    })->name('admin.broadcast.index');

    Route::get('/checkin', function () {
        return 'Halaman Scanner QR Check-in (Coming Soon)';
    })->name('admin.checkin.index');
});