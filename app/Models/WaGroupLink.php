<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaGroupLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_category',
        'wa_group_link',
    ];

    /**
     * Kategori menjadi satu-satunya pembeda link grup WA.
     * Gelombang (Early Bird / Regular) dengan kategori sama tetap satu grup.
     */
    protected static function canonicalMap(): array
    {
        return [
            'Basic'                     => 'Basic',
            'Advance'                   => 'Advanced',
            'Advanced'                  => 'Advanced',
            'Basic-Advance'             => 'Basic-Advanced',
            'Basic-Advanced'            => 'Basic-Advanced',
            'Basic - Advance'           => 'Basic-Advanced',
            'Basic - Advanced'          => 'Basic-Advanced',
            'Online'                    => 'Online',
            'Workshop'                  => 'Workshop',
            'Basic-Advance + Workshop'  => 'Basic-Advanced + Workshop',
            'Basic-Advanced + Workshop' => 'Basic-Advanced + Workshop',
            'Basic - Advance + Workshop'=> 'Basic-Advanced + Workshop',
            'Basic - Advanced + Workshop'=> 'Basic-Advanced + Workshop',
        ];
    }

    /**
     * Samakan penulisan kategori agar legacy (Advance, Basic-Advance, dsb.)
     * ikut terpetakan ke ejaan benar: Advanced, Basic-Advanced.
     */
    public static function normalizeCategory(string $category): string
    {
        $key = trim($category);

        return static::canonicalMap()[$key] ?? $key;
    }

    /**
     * Ambil link grup untuk sebuah kategori.
     */
    public static function linkFor(string $category): ?string
    {
        $row = static::where('ticket_category', static::normalizeCategory($category))->first();

        return $row?->wa_group_link;
    }

    /**
     * Daftar grup yang relevan untuk sebuah kategori tiket.
     * Kategori tunggal -> 1 grup; kategori combo (Basic-Advanced, + Workshop)
     * diperluas ke grup komponennya (Basic + Advanced / Basic + Advanced + Workshop)
     * sehingga pembeli tetap mendapat tombol join untuk semua grup terkait.
     */
    public static function groupCategoriesFor(string $category): array
    {
        $map = [
            'Basic'                    => ['Basic'],
            'Advanced'                 => ['Advanced'],
            'Online'                   => ['Online'],
            'Workshop'                 => ['Workshop'],
            'Basic-Advanced'           => ['Basic', 'Advanced'],
            'Basic-Advanced + Workshop'=> ['Basic', 'Advanced', 'Workshop'],
        ];

        $normalized = static::normalizeCategory($category);

        return $map[$normalized] ?? [$normalized];
    }
}