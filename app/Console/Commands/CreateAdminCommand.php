<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Opens the first door on a fresh install.
 *
 * Registration is invite-only and the demo users are seeded only in local and
 * testing, so a freshly deployed server has no account at all and nobody can
 * sign in to create one. This command is the way in.
 *
 * Every value can be passed as an option, because the command runner on a
 * managed host is not a terminal — a prompt there would simply hang.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'areen:create-admin
        {--name= : The person\'s name}
        {--email= : The address they will sign in with}
        {--password= : Their password; omit to be prompted, or use --generate}
        {--generate : Generate a strong password and print it once}
        {--role=admin : admin or coach}';

    protected $description = 'Create the first administrator on a fresh install';

    public function handle(): int
    {
        $role = (string) $this->option('role');

        if (UserRole::tryFrom($role) === null || $role === UserRole::Trainee->value) {
            $this->components->error('Role must be admin or coach. Trainees are created from the panel.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');

        $generated = null;

        if ($this->option('generate')) {
            $generated = str()->password(16);
            $password = $generated;
        } else {
            $password = $this->option('password') ?: $this->secret('Password');
        }

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', Rule::unique('users', 'email')],
                'password' => ['required', Password::min(8)],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
            'locale' => config('app.locale'),
            'is_active' => true,
        ]);

        // Nothing signs in without a verified address, and there is no inbox to
        // confirm from on a first deploy.
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->newLine();
        $this->components->info("Created {$role}: {$email}");

        if ($generated !== null) {
            // Shown once and never stored anywhere readable.
            $this->components->warn("Password: {$generated}");
            $this->components->warn('Copy it now — it is not recoverable.');
        }

        $this->line('  Sign in at '.rtrim((string) config('app.url'), '/').route('admin.login', absolute: false));
        $this->newLine();

        return self::SUCCESS;
    }
}
