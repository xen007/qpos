<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CreateInitialAdmin extends Command
{
    protected $signature = 'qpos:admin:create';

    protected $description = 'Create the first QPOS administrator interactively';

    public function handle(): int
    {
        $adminRole = Role::query()->where('name', 'Admin')->first();

        if (!$adminRole) {
            $this->error('The Admin role is missing. Run database migrations and seeders first.');

            return self::FAILURE;
        }

        if ($adminRole->users()->exists()) {
            $this->info('An administrator already exists. No changes were made.');

            return self::SUCCESS;
        }

        $name = trim((string) $this->ask('Administrator name'));
        $email = strtolower(trim((string) $this->ask('Administrator email')));

        if ($name === '' || mb_strlen($name) > 255 || mb_strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('A name (up to 255 characters) and a valid email address (up to 254 characters) are required.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('That email address is already in use.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password (minimum 12 characters)');

        if (mb_strlen($password) < 12) {
            $this->error('The password must contain at least 12 characters.');

            return self::FAILURE;
        }

        if (config('hashing.driver') === 'bcrypt' && strlen($password) > 72) {
            $this->error('With bcrypt, the password cannot exceed 72 bytes.');

            return self::FAILURE;
        }

        $passwordConfirmation = (string) $this->secret('Confirm password');

        if (!hash_equals($password, $passwordConfirmation)) {
            $this->error('The passwords do not match.');

            return self::FAILURE;
        }

        $created = DB::transaction(function () use ($name, $email, $password, $adminRole): bool {
            $lockedAdminRole = Role::query()
                ->whereKey($adminRole->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAdminRole->users()->exists()) {
                return false;
            }

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'username' => (string) Str::uuid(),
            ]);

            $user->assignRole($lockedAdminRole);

            return true;
        });

        if (!$created) {
            $this->info('An administrator was created by another process. No changes were made.');

            return self::SUCCESS;
        }

        $this->info('Administrator created. You can now sign in to QPOS.');

        return self::SUCCESS;
    }
}
