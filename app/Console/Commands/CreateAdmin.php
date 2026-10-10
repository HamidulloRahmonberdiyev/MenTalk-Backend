<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[AsCommand(name: 'admin:create', description: 'Create (or promote) an admin panel user')]
final class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email?} {--name=}';

    public function handle(): int
    {
        $email = (string) ($this->argument('email') ?? text('Email', required: true));
        $password = password('Parol (kamida 12 belgi)', required: true, validate: fn (string $value) => strlen($value) < 12 ? 'Kamida 12 belgi.' : null);

        $user = User::withTrashed()->firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->fill([
                'google_id' => 'admin:'.str()->uuid(),
                'name' => (string) ($this->option('name') ?: str($email)->before('@')),
            ]);
        }

        $user->forceFill(['is_admin' => true, 'password' => $password, 'blocked_at' => null])->save();

        $this->components->info("Admin tayyor: {$email}");

        return self::SUCCESS;
    }
}
