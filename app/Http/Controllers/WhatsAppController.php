<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    /**
     * GET /api/whatsapp/webhook
     *
     * Verifikasi webhook oleh Meta. Meta mengirim parameter:
     *   hub.mode, hub.verify_token, hub.challenge
     *
     * Jika valid, kembalikan hub.challenge sebagai plain text (HTTP 200).
     */
    public function verify(Request $request)
    {
        // Laravel menormalkan titik (.) di nama param menjadi underscore (_).
        // Meta mengirim hub.mode, hub.verify_token, hub.challenge — baca kedua bentuk
        // agar aman baik melalui Laravel maupun server/proxy lain.
        $mode = $request->query('hub.mode', $request->query('hub_mode'));
        $verifyToken = $request->query('hub.verify_token', $request->query('hub_verify_token'));
        $challenge = $request->query('hub.challenge', $request->query('hub_challenge'));

        $expectedToken = config('services.whatsapp.webhook_verify_token');

        if (
            $mode === 'subscribe'
            && is_string($verifyToken)
            && !empty($expectedToken)
            && hash_equals($expectedToken, $verifyToken)
            && $challenge !== null
        ) {
            return response($challenge, 200);
        }

        Log::warning('WhatsApp Webhook verification failed', [
            'mode'         => $mode,
            'verify_token' => is_string($verifyToken) ? substr($verifyToken, 0, 4) . '***' : null,
        ]);

        return response('Verification token mismatch', 403);
    }

    /**
     * POST /api/whatsapp/webhook
     *
     * Menerima event/notifikasi dari WhatsApp Cloud API.
     *
     * Tahap pertama: hanya terima & catat payload untuk debugging.
     * Tidak melakukan proses berat atau update database.
     *
     * TODO (Signature Verification):
     * Validasi X-Hub-Signature-256 (HMAC-SHA256 dari raw body menggunakan APP_SECRET)
     * membutuhkan env WHATSAPP_APP_SECRET yang belum tersedia di .env.
     * Tambahkan implementasi setelah APP_SECRET disediakan, contoh:
     *
     *   $rawBody = $request->getContent();
     *   $expected = 'sha256=' . hash_hmac('sha256', $rawBody, config('services.whatsapp.app_secret'));
     *   if (!hash_equals($expected, $request->header('X-Hub-Signature-256'))) {
     *       abort(403);
     *   }
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            Log::info('WhatsApp Webhook Received', [
                'entry_count' => count($request->input('entry', [])),
                'object'      => $request->input('object'),
                'payload'     => $request->all(),
            ]);
        } catch (\Throwable $e) {
            Log::error('WhatsApp Webhook processing error', [
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['status' => 'ok'], 200);
    }
}