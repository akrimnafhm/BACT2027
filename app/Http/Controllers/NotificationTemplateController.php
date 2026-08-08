<?php

namespace App\Http\Controllers;

use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    /**
     * Halaman Kelola Template Notifikasi Tiket Lunas.
     */
    public function index()
    {
        $templates = NotificationTemplate::orderBy('id')->get();

        return view('admin.notifications', compact('templates'));
    }

    /**
     * Simpan perubahan template notifikasi.
     */
    public function update(Request $request)
    {
        $keys = ['ticket_paid_wa', 'ticket_paid_email'];

        foreach ($keys as $key) {
            $template = NotificationTemplate::where('key', $key)->first();
            if (!$template) {
                continue;
            }

            $body = $request->input($key . '_body');

            // Field body tidak dikirim = form untuk template ini tidak disubmit
            if ($body === null) {
                continue;
            }

            $template->update([
                'subject'    => $request->input($key . '_subject') ?: null,
                'body'       => $body,
                'include_qr' => $request->boolean($key . '_include_qr'),
                'is_active'  => $request->boolean($key . '_is_active'),
            ]);
        }

        return back()->with('success', 'Template notifikasi berhasil diperbarui!');
    }
}
