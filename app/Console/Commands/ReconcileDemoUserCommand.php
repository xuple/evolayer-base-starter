<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

#[Signature('evolayer:starter:reconcile-demo-user')]
#[Description('Report a legacy Starter demonstration account without deleting application data.')]
final class ReconcileDemoUserCommand extends Command
{
    public function handle(): int
    {
        if (config('evolayer.base.profile') !== 'application') {
            $this->components->error('The demo account may only be reconciled while the application profile is effective.');

            return self::FAILURE;
        }

        if (! Schema::hasTable('users')) {
            $this->components->info('No users table exists; no demonstration account requires reconciliation.');

            return self::SUCCESS;
        }

        $user = User::query()->where('email', 'test@example.com')->first();

        if ($user === null) {
            $this->components->info('The known demonstration account is absent.');

            return self::SUCCESS;
        }

        $this->components->error('A legacy demonstration account is present; it was preserved because this Starter cannot prove it has no downstream data. Reconcile it deliberately, then rerun profile verification.');

        return self::FAILURE;
    }
}
