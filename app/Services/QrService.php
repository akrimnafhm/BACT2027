<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Log;

class QrService
{
    /**
     * Buat gambar QR (PNG) dan simpan ke storage lokal.
     *
     * @param string $data  Isi QR (mis. checkin_token)
     * @param string $name  Nama file tanpa ekstensi (mis. booking ID / token)
     * @return string|null  Path absolut file PNG, atau null jika gagal
     */
    public function generatePng(string $data, string $name): ?string
    {
        try {
            $dir = storage_path('app/qr');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $filename = preg_replace('/[^A-Za-z0-9\-_]/', '-', $name) . '.png';
            $path = $dir . DIRECTORY_SEPARATOR . $filename;

            // Selalu generate ulang agar QR selalu segar & valid
            $qr = new QrCode(data: $data, size: 320, margin: 10);
            $writer = new PngWriter();
            $result = $writer->write($qr);
            file_put_contents($path, $result->getString());

            return $path;
        } catch (\Throwable $e) {
            Log::error('Gagal generate QR: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Hapus file QR bila sudah tidak dibutuhkan.
     */
    public function delete(string $path): void
    {
        try {
            if ($path && is_file($path)) {
                unlink($path);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal hapus QR: ' . $e->getMessage());
        }
    }
}
