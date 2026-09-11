<?php

namespace App\Helpers;

class YouTubeHelper
{
    /**
     * Ekstrak Video ID dari berbagai format URL YouTube.
     * Mendukung: watch?v=, youtu.be/, live/, embed/, shorts/
     */
    public static function extractVideoId(string $url): ?string
    {
        $url = trim($url);
        if (empty($url)) {
            return null;
        }

        $patterns = [
            // youtube.com/watch?v=VIDEO_ID
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
            // youtu.be/VIDEO_ID
            '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
            // youtube.com/live/VIDEO_ID
            '/youtube\.com\/live\/([a-zA-Z0-9_-]{11})/',
            // youtube.com/embed/VIDEO_ID
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
            // youtube.com/shorts/VIDEO_ID
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
            // youtube.com/v/VIDEO_ID (legacy)
            '/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        // Jika sudah berupa Video ID murni (11 karakter alphanumeric + _ -)
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
            return $url;
        }

        return null;
    }

    /**
     * Generate URL embed YouTube dari Video ID.
     */
    public static function getEmbedUrl(string $videoId, bool $autoplay = false, bool $mute = false, ?string $origin = null): string
    {
        $params = [
            'rel' => 0,
            'modestbranding' => 1,
            'playsinline' => 1,
        ];

        if ($autoplay) {
            $params['autoplay'] = 1;
            if ($mute) {
                $params['mute'] = 1;
            }
        }

        if ($origin) {
            $params['origin'] = $origin;
        }

        return 'https://www.youtube.com/embed/' . $videoId . '?' . http_build_query($params);
    }

    /**
     * Generate URL embed untuk live streaming.
     */
    public static function getLiveEmbedUrl(string $videoId, bool $autoplay = false, ?string $origin = null): string
    {
        $baseUrl = self::getEmbedUrl($videoId, $autoplay, false, $origin);
        // Untuk live stream, YouTube otomatis menampilkan live chat jika video adalah live stream.
        // Tambahkan enablejsapi=1 untuk kompatibilitas API.
        return $baseUrl . '&enablejsapi=1';
    }

    /**
     * Validasi apakah URL YouTube valid dan dapat diekstrak Video ID-nya.
     */
    public static function isValidYouTubeUrl(string $url): bool
    {
        return self::extractVideoId($url) !== null;
    }

    /**
     * Get thumbnail URL dari Video ID.
     */
    public static function getThumbnailUrl(string $videoId, string $quality = 'maxresdefault'): string
    {
        $qualities = ['default', 'mqdefault', 'hqdefault', 'sddefault', 'maxresdefault'];
        if (!in_array($quality, $qualities)) {
            $quality = 'maxresdefault';
        }
        return "https://img.youtube.com/vi/{$videoId}/{$quality}.jpg";
    }
}