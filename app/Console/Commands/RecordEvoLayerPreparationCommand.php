<?php

namespace App\Console\Commands;

use App\Support\EvoLayer\StarterPreparationEvidence;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Hidden;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('evolayer:starter:record-preparation {--internal : Confirm invocation by the reviewed preparation pipeline}')]
#[Description('Record Starter preparation evidence from a freshly bootstrapped application process.')]
#[Hidden]
final class RecordEvoLayerPreparationCommand extends Command
{
    public function handle(StarterPreparationEvidence $evidence): int
    {
        if (! (bool) $this->option('internal')) {
            $this->components->error('Preparation evidence may only be recorded by evolayer:starter:prepare.');

            return self::FAILURE;
        }

        try {
            $evidence->write(
                (string) config('evolayer.base.profile', 'demo'),
                (array) config('evolayer.base.examples', []),
                (array) config('evolayer.base.features', []),
            );
        } catch (Throwable) {
            $this->components->error('Starter preparation evidence could not be written.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
