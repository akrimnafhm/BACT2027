<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
        User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        // 3. Arahkan ke login dengan pesan sukses
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
}