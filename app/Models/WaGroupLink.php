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
            'Advance'                   => 'Advance',
            'Advanced'                  => 'Advance',
            'Basic-Advance'             => 'Basic-Advance',
            'Basic-Advanced'            => 'Basic-Advance',
            'Basic - Advance'           => 'Basic-Advance',
            'Online'                    => 'Online',
            'Workshop'                  => 'Workshop',
            'Basic-Advance + Workshop'  => 'Basic-Advance + Workshop',
            'Basic - Advance + Workshop'=> 'Basic-Advance + Workshop',
        ];
    }

    /**
     * Samakan penulisan kategori agar legacy (Advanced, Basic-Advanced, dsb.)
     * ikut terpetakan ke grup yang sama.
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