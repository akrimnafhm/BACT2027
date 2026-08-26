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
     * Semua kategori tiket yang memiliki grup WhatsApp.
     * Sumber tunggal untuk dropdown screening & halaman Grup WhatsApp.
     */
    public static function allCategories(): array
    {
        return ['Basic', 'Advanced', 'Basic-Advanced', 'Online', 'Workshop', 'Advanced-Workshop', 'Basic-Advanced + Workshop'];
    }

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
            'Advanced-Workshop'         => 'Advanced-Workshop',
            'Advanced - Workshop'       => 'Advanced-Workshop',
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

}