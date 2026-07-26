<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('app:create-admin {email? : Admin email address} {--name= : Admin display name}')]
#[Description('Create a new administrator or promote an existing user')]
class CreateAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $emailInput = $this->argument('email') ?? $this->ask('ایمیل مدیر');
        $nameInput = $this->option('name') ?? $this->ask('نام مدیر', 'مدیر');
        $password = $this->secret('رمز عبور (حداقل ۸ کاراکتر)');
        $email = is_string($emailInput) ? Str::lower(trim($emailInput)) : '';
        $name = is_string($nameInput) ? trim($nameInput) : '';

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('ایمیل واردشده معتبر نیست.');

            return self::FAILURE;
        }

        if ($name === '' || ! is_string($password) || Str::length($password) < 8) {
            $this->error('نام و رمز عبور حداقل ۸ کاراکتری الزامی هستند.');

            return self::FAILURE;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'is_admin' => true,
            ],
        );

        $this->info('حساب مدیر آماده شد.');

        return self::SUCCESS;
    }
}
