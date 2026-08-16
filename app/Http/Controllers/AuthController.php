<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    // Menampilkan halaman Login
    public function showLogin()
    {
        return view('login');
    }

    // Memproses data Login
    public function processLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // --- LOGIKA REDIRECT BERDASARKAN ROLE ---
            if (Auth::user()->role === 'admin') {
                // Jika Admin, lempar ke Dashboard Admin
                return redirect()->route('admin.dashboard')->with('success', 'Selamat datang di Panel Admin!');
            }

            // Jika Peserta, lempar ke Homepage (atau halaman profil)
            return redirect('/')->with('success', 'Login berhasil!');
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    // Menampilkan halaman Register
    public function showRegister()
    {
        return view('register');
    }

    // Memproses data Register
    public function processRegister(Request $request)
    {
        // 1. Validasi input (phone_number dihapus, tambah 'confirmed' di password)
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed', // Laravel akan otomatis mencari field 'password_confirmation'
        ]);

        // 2. Simpan ke database (tanpa phone_number)
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        // 3. Hubungkan tiket peserta manual yang emailnya belum terdaftar saat dibuat.
        //    (Admin menambahkan peserta manual; saat pemilik email mendaftar, tiketnya otomatis muncul.)
        \App\Models\TicketBooking::whereNull('user_id')
            ->where('gmail_account', $user->email)
            ->update(['user_id' => $user->id]);

        // 4. Arahkan ke login dengan pesan sukses
        return redirect('/login')->with('success', 'Registrasi berhasil! Silakan login dengan akun Anda.');
    }

    // Memproses Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // ==========================================
    // LUPA PASSWORD (Multi-Step: Email -> Kode -> Password Baru)
    // ==========================================

    /**
     * Menampilkan halaman lupa password.
     */
    public function showForgotPassword()
    {
        return view('forgot-password');
    }

    /**
     * Step 1: Kirim kode 6 digit ke email (disimulasikan via log).
     */
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        // Jangan ungkap apakah email terdaftar (cegah user enumeration)
        if (!$user) {
            return back()->with('success', 'Jika email terdaftar di sistem, kode reset telah dikirim.');
        }

        // Buat & simpan kode 6 digit, berlaku 10 menit
        $code = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->reset_code = $code;
        $user->reset_code_expires_at = now()->addMinutes(10);
        $user->save();

        // Simulasi pengiriman email (fallback: tercatat di log)
        Log::info("SIMULASI EMAIL BACT: Kode reset password untuk {$user->email} adalah [ {$code} ]");

        // Kirim email sungguhan via Brevo (jika mailer sudah dikonfigurasi)
        try {
            Mail::to($user->email)->send(new ResetPasswordCodeMail($user->name, $code));
            Log::info("Email reset password terkirim ke {$user->email}");
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim email reset password: ' . $e->getMessage());
        }

        // Simpan state step di session
        session(['reset_email' => $email, 'reset_attempts' => 0]);

        return redirect()->route('forgot-password')
                         ->with('success', 'Kode reset 6 digit telah dikirim ke email Anda. Silakan cek inbox email Anda.');
    }

    /**
     * Step 2: Verifikasi kode 6 digit.
     */
    public function verifyResetCode(Request $request)
    {
        $email = session('reset_email');

        if (!$email) {
            return redirect()->route('forgot-password');
        }

        $request->validate([
            'code' => 'required|digits:6',
        ], [
            'code.required' => 'Kode wajib diisi.',
            'code.digits'   => 'Kode harus berjumlah 6 digit angka.',
        ]);

        $user = User::where('email', $email)->first();

        // Hapus state jika user sudah tidak ada
        if (!$user) {
            $request->session()->forget(['reset_email', 'reset_attempts', 'reset_verified']);
            return redirect()->route('forgot-password')->withErrors(['email' => 'Sesi tidak valid. Silakan ulangi dari awal.']);
        }

        $attempts = (int) session('reset_attempts', 0);

        // Batas percobaan maksimal 5x, jika lewat wajib kirim ulang
        if ($attempts >= 5) {
            return back()->withErrors(['code' => 'Terlalu banyak percobaan. Silakan kirim ulang kode.']);
        }

        $expired = !$user->reset_code_expires_at || now()->greaterThan($user->reset_code_expires_at);

        if ($expired || $user->reset_code !== $request->code) {
            session(['reset_attempts' => $attempts + 1]);
            $remaining = 5 - ($attempts + 1);
            return back()->withErrors([
                'code' => $expired
                    ? 'Kode sudah kedaluwarsa. Silakan kirim ulang kode.'
                    : 'Kode salah. Sisa percobaan: ' . max($remaining, 0) . 'x.',
            ]);
        }

        // Kode benar: tandai terverifikasi & lanjut ke step 3
        session(['reset_verified' => true]);

        return redirect()->route('forgot-password');
    }

    /**
     * Step 3: Setel password baru.
     */
    public function processResetPassword(Request $request)
    {
        $email = session('reset_email');

        if (!$email || !session('reset_verified')) {
            return redirect()->route('forgot-password');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required'  => 'Password baru wajib diisi.',
            'password.min'       => 'Password baru minimal harus terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = User::where('email', $email)->first();

        if (!$user) {
            $request->session()->forget(['reset_email', 'reset_attempts', 'reset_verified']);
            return redirect()->route('forgot-password')->withErrors(['email' => 'Sesi tidak valid. Silakan ulangi dari awal.']);
        }

        $user->password = Hash::make($request->password);
        $user->reset_code = null;
        $user->reset_code_expires_at = null;
        $user->save();

        // Bersihkan state sesi reset
        $request->session()->forget(['reset_email', 'reset_attempts', 'reset_verified']);

        return redirect()->route('login')
                         ->with('success', 'Password berhasil diperbarui! Silakan login dengan password baru Anda.');
    }
}