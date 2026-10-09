<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create an administrator account or promote an existing user';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->components->error('This command requires an interactive terminal so the password can be entered securely.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Administrator name'));
        $email = mb_strtolower(trim((string) $this->ask('Administrator email')));
        $password = (string) $this->secret('Administrator password (minimum 12 characters)');
        $passwordConfirmation = (string) $this->secret('Confirm password');

        $validator = Validator::make(
            compact('name', 'email', 'password', 'passwordConfirmation'),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:12', 'same:passwordConfirmation'],
                'passwordConfirmation' => ['required', 'string'],
            ],
            [
                'password.same' => 'The password confirmation does not match.',
            ],
        );

        if ($validator->fails()) {
            $this->components->error($validator->errors()->first());

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user?->is_admin) {
            $this->components->error('This account is already an administrator. No changes were made.');

            return self::FAILURE;
        }

        if ($user && ! $this->confirm("Promote {$email} and replace its password?", false)) {
            $this->components->warn('No changes were made.');

            return self::SUCCESS;
        }

        $user ??= new User;
        $user->name = $user->exists ? $user->name : $name;
        $user->email = $email;
        $user->password = $password;
        $user->is_admin = true;
        $user->save();

        $this->components->info("Administrator account {$email} is ready.");

        return self::SUCCESS;
    }
}
