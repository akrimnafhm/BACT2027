<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password',
        'nik',
        'gender',
        'degree_front',
        'degree_back',
        'address',
        'institution',
        'wa_joined_groups',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'email_otp_expires_at' => 'datetime',
            'otp_sent_at' => 'datetime',
            'email_otp_sent_at' => 'datetime',
            'wa_joined_groups' => 'array',
            'password' => 'hashed',
        ];
    }

    /**
     * Daftar grup WhatsApp yang sudah di-join peserta.
     *
     * @return array<array{group: string, joined_at: string|null}>
     */
    public function waJoinedGroups(): array
    {
        $groups = $this->wa_joined_groups ?? [];

        // Toleransi format lama (string tunggal) yang mungkin tersisa.
        if (is_string($groups)) {
            $decoded = json_decode($groups, true);
            $groups = is_array($decoded) ? $decoded : [];
        }

        return array_values(array_filter($groups, fn ($entry) => is_array($entry) && isset($entry['group'])));
    }

    /**
     * Cek apakah peserta sudah join grup tertentu.
     */
    public function hasJoinedWaGroup(string $group): bool
    {
        foreach ($this->waJoinedGroups() as $entry) {
            if (($entry['group'] ?? null) === $group) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tandai peserta sudah join grup tertentu (idempoten).
     */
    public function markJoinedWaGroup(string $group, ?Carbon $joinedAt = null): void
    {
        $groups = $this->waJoinedGroups();

        if (! $this->hasJoinedWaGroup($group)) {
            $groups[] = [
                'group' => $group,
                'joined_at' => ($joinedAt ?? now())->format('Y-m-d H:i:s'),
            ];
        }

        $this->wa_joined_groups = $groups;
        $this->save();
    }

    /**
     * Hapus tanda join grup tertentu (idempoten).
     */
    public function removeJoinedWaGroup(string $group): void
    {
        $groups = array_values(array_filter(
            $this->waJoinedGroups(),
            fn ($entry) => ($entry['group'] ?? null) !== $group
        ));

        $this->wa_joined_groups = $groups ?: null;
        $this->save();
    }
}
