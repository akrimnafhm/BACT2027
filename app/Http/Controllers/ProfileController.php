<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function edit()
    {
        // Mengambil data user yang sedang login
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // 1. Aturan validasi ketat (tanpa address & tanpa gelar)
        $rules = [
            'name'         => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'nik'          => 'required|string|size:16', // Wajib persis 16 digit NIK
            'gender'       => 'required|in:Laki-laki,Perempuan', // Wajib pilih
        ];

        // 2. Jika user mengisi kolom password baru, tambahkan validasi password
        if ($request->filled('new_password')) {
            $rules['current_password'] = 'required|current_password';
            $rules['new_password']     = 'required|string|min:8|confirmed';
        }

        $request->validate($rules, [
            'name.required'                     => 'Nama lengkap wajib diisi.',
            'phone_number.required'             => 'Nomor WhatsApp wajib diisi.',
            'nik.required'                      => 'NIK wajib diisi.',
            'nik.size'                          => 'NIK harus berjumlah persis 16 digit angka.',
            'gender.required'                   => 'Silakan pilih jenis kelamin Anda.',
            'current_password.required'         => 'Mohon masukkan password saat ini untuk keamanan.',
            'current_password.current_password' => 'Password saat ini yang Anda masukkan tidak sesuai.',
            'new_password.min'                  => 'Password baru minimal harus terdiri dari 8 karakter.',
            'new_password.confirmed'            => 'Konfirmasi password baru tidak cocok.',
        ]);

        // 3. Jika nomor HP berubah, cabut status verifikasinya agar wajib verifikasi ulang
        if ($user->phone_number !== $request->phone_number) {
            $user->phone_verified_at = null; 
        }

        // 4. Update data profil ke database (Tanpa Address)
        $user->name         = $request->name;
        $user->phone_number = $request->phone_number;
        $user->nik          = $request->nik;
        $user->gender       = $request->gender;

        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return back()->with('success', 'Profil dan keamanan akun berhasil diperbarui!');
    }

    public function sendOtp(Request $request)
    {
        // 1. Validasi apakah form nomor HP kosong saat tombol diklik
        $request->validate([
            'phone_number' => 'required|string|max:20'
        ], [
            'phone_number.required' => 'Silakan isi nomor WhatsApp terlebih dahulu sebelum meminta OTP.'
        ]);

        $user = Auth::user();
        
        // 2. Langsung perbarui nomor HP user dengan yang baru diketik
        $user->phone_number = $request->phone_number;
        $user->phone_verified_at = null; // Pastikan statusnya reset
        
        // 3. Buat dan simpan OTP
        $otp = rand(100000, 999999);
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        Log::info("SIMULASI WHATSAPP BACT: Kode OTP untuk {$user->phone_number} adalah [ {$otp} ]");

        return back()->with('otp_sent', true)->with('success', "Kode OTP telah dikirim ke nomor {$user->phone_number}!");
    }

    public function verifyOtp(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'otp_code' => 'required|numeric'
        ]);

        // Cek apakah kodenya sama dan belum kedaluwarsa
        if ($user->otp_code === $request->otp_code && now()->lessThanOrEqualTo($user->otp_expires_at)) {
            $user->phone_verified_at = now();
            $user->otp_code = null; // Bersihkan kode setelah dipakai
            $user->otp_expires_at = null;
            $user->save();
            
            return back()->with('success', 'Nomor WhatsApp berhasil diverifikasi!');
        }

        // Jika salah, form OTP tetap dimunculkan beserta pesan error
        return back()->with('otp_sent', true)->withErrors(['otp_code' => 'Kode OTP salah atau sudah kedaluwarsa.']);
    }
}