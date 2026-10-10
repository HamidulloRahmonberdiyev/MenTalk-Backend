<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** Creates or promotes the first admin from ADMIN_EMAIL / ADMIN_PASSWORD. Safe to run repeatedly. */
final class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.seed.email') ?? 'admin@admin.com';
        $password = config('admin.seed.password') ?? 'password';

        if (! $email || ! $password) {
            $this->command?->warn('AdminSeeder skipped: set ADMIN_EMAIL and ADMIN_PASSWORD in .env.');

            return;
        }

        $user = User::withTrashed()->firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->fill([
                'google_id' => 'admin:' . str()->uuid(),
                'name' => config('admin.seed.name'),
            ]);
        }

        $user->forceFill(['is_admin' => true, 'password' => $password, 'blocked_at' => null])->save();

        if ($user->trashed()) {
            $user->restore();
        }

        $this->command?->info("Admin ready: {$email}");
    }
}
