<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $mode = SiteSetting::value('site_mode', 'normal');

        if ($mode !== 'maintenance') {
            return $next($request);
        }

        // 1) Halaman admin hanya boleh diakses admin (IsAdmin tetap memfilter lebih lanjut).
        if ($request->is('admin') || $request->is('admin/*')) {
            return $next($request);
        }

        // 2) Endpoint API/webhook (mis. notifikasi DOKU) tetap berjalan agar pembayaran tercatat.
        if ($request->is('api/*')) {
            return $next($request);
        }

        // 3) Health check.
        if ($request->is('up')) {
            return $next($request);
        }

        // 4) Logout tetap boleh agar admin/participant bisa keluar.
        if ($request->is('logout')) {
            return $next($request);
        }

        // 5) Halaman login & lupa password boleh diakses — tetapi hanya admin yang boleh login.
        if ($request->is('login') || $request->is('forgot-password') || $request->is('forgot-password/*')) {
            // Jika peserta mencoba login, tampilkan info maintenance.
            if ($request->is('login') && $request->isMethod('post')) {
                $email = $request->input('email');
                if ($email) {
                    $user = User::where('email', $email)->first();
                    if ($user && $user->role !== 'admin') {
                        return $this->maintenanceResponse($request);
                    }
                }
            }

            return $next($request);
        }

        // Semua halaman publik lainnya diblokir selama maintenance.
        return $this->maintenanceResponse($request);
    }

    private function maintenanceResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Situs sedang dalam mode maintenance. Silakan kembali lagi nanti.',
            ], 503);
        }

        return response(view('maintenance'), 503);
    }
}