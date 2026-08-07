<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    protected $token;
    protected $apiUrl;

    public function __construct()
    {
        $this->token = config('services.fonnte.token', env('FONNTE_API_TOKEN'));
        $this->apiUrl = config('services.fonnte.url', env('FONNTE_API_URL', 'https://api.fonnte.com/send'));
    }

    /**
     * Helper untuk standarisasi nomor HP Indonesia (08xxx -> 628xxx)
     */
    public function formatPhoneNumber($phone)
    {
        // Hapus karakter selain angka
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Jika diawali angka 0, ganti dengan 62
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Kirim Pesan WhatsApp Tunggal (Untuk Konfirmasi Tiket & Hotel)
     *
     * @param string $target Nomor HP tujuan (08xxx / 628xxx)
     * @param string $message Isi pesan teks
     * @return array|bool
     */
    public function sendMessage($target, $message)
    {
        $formattedPhone = $this->formatPhoneNumber($target);

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->apiUrl, [
                'target'  => $formattedPhone,
                'message' => $message,
                'countryCode' => '62', // Default kode negara Indonesia
            ]);

            $result = $response->json();

            // Catat log jika pengiriman gagal dari sisi Fonnte
            if (!isset($result['status']) || $result['status'] != true) {
                Log::warning('Fonnte Send Message Failed', [
                    'target' => $formattedPhone,
                    'response' => $result
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Fonnte Connection Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim Pesan Broadcast dengan Delay (Untuk Admin Broadcast)
     *
     * @param array|string $targets Nomor HP (bisa dipisah koma: "62811,62812" atau array)
     * @param string $message Isi pesan broadcast
     * @param int $delay Jeda pengiriman per pesan dalam detik (min. 2-5 detik agar aman)
     * @return array|bool
     */
    public function sendBroadcast($targets, $message, $delay = 3)
    {
        if (is_array($targets)) {
            // Format setiap nomor lalu gabungkan dengan koma untuk format Fonnte
            $formattedTargets = array_map([$this, 'formatPhoneNumber'], $targets);
            $targetString = implode(',', $formattedTargets);
        } else {
            $targetString = $this->formatPhoneNumber($targets);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->apiUrl, [
                'target'  => $targetString,
                'message' => $message,
                'delay'   => (string) $delay, // Fitur delay otomatis dari Fonnte
                'countryCode' => '62',
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Fonnte Broadcast Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim Pesan Broadcast Terpersonalisasi (Anti-Spam WA)
     * Menggunakan parameter 'data' bulk JSON dari Fonnte
     *
     * @param array $recipients Array berisi list ['name' => '...', 'phone' => '...']
     * @param string $templateMessage Pesan yang mengandung placeholder {nama}
     * @param int $delay Jeda detik antar pengiriman
     * @return array|bool
     */
    public function sendBulkPersonalized(array $recipients, $templateMessage, $delay = 3)
    {
        $dataPayload = [];

        foreach ($recipients as $recipient) {
            $formattedPhone = $this->formatPhoneNumber($recipient['phone']);

            // Ganti placeholder {nama} atau [nama] dengan nama asli peserta
            $customMessage = str_replace(
                ['{nama}', '{name}', '[nama]', '[name]'],
                $recipient['name'] ?? 'Peserta',
                $templateMessage
            );

            $dataPayload[] = [
                'target'  => $formattedPhone,
                'message' => $customMessage,
                'delay'   => (string) $delay,
            ];
        }

        try {
            // Fonnte mendukung pengiriman bulk dinamis via parameter 'data' (JSON string)
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->apiUrl, [
                'data'        => json_encode($dataPayload),
                'countryCode' => '62',
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Fonnte Bulk Broadcast Error: ' . $e->getMessage());
            return false;
        }
    }
}