<?php

namespace App\Console\Commands;

use App\Models\TicketBooking;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Backfill SATU KALI untuk peserta manual lama yang user_id-nya masih null.
 *
 * Scope sempit & aman:
 * - Hanya source = manual, user_id IS NULL, status bukan cancelled.
 * - Booking website / seeder / yang sudah ter-link TIDAK disentuh.
 * - Data peserta tidak diubah; hanya INSERT users + UPDATE user_id.
 * - Idempoten: dijalankan ulang tidak ada efek (putaran kedua tidak
 *   menemukan apa-apa karena user_id sudah terisi).
 */
class LinkManualBookingsToUsers extends Command
{
    protected $signature = 'bookings:link-manual-users {--dry-run : Tampilkan rencana tanpa menyimpan}';

    protected $description = 'Buatkan akun User pendamping untuk peserta manual lama yang belum ter-link';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $bookings = TicketBooking::where('source', 'manual')
            ->whereNull('user_id')
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->get();

        $this->info("Manual yatim (bukan cancelled): {$bookings->count()}");

        $linked = 0;
        $skipped = 0;

        foreach ($bookings as $booking) {
            // 1. Pakai akun yang sudah ada bila emailnya cocok (jangan buat duplikat).
            $user = User::where('email', $booking->gmail_account)->first();
            $action = $user ? "pakai user #{$user->id}" : 'buat user baru';

            $this->line("  booking #{$booking->id} ({$booking->gmail_account}, {$booking->status}) -> {$action}");

            if ($dryRun) {
                continue;
            }

            if (! $user) {
                // Email bentrok (dibuat orang lain belakangan): jangan pakai akun
                // orang lain — buatkan email alias agar tetap unik.
                $email = $booking->gmail_account;
                if (User::where('email', $email)->exists()) {
                    $email = 'manual+'.$booking->id.'+'.$email;
                }

                $nikTaken = $booking->nik && User::where('nik', $booking->nik)->exists();

                $user = User::create([
                    'name' => $booking->full_name,
                    'email' => $email,
                    'phone_number' => $booking->whatsapp_number,
                    'password' => Hash::make((string) ($booking->nik ?: $booking->id)),
                    'nik' => $nikTaken ? null : $booking->nik,
                ]);
            }

            $booking->update(['user_id' => $user->id]);
            $linked++;
        }

        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN — tidak ada data yang disimpan.');

            return self::SUCCESS;
        }

        $this->info("Selesai: {$linked} booking manual ter-link, {$skipped} dilewati.");

        return self::SUCCESS;
    }
}
