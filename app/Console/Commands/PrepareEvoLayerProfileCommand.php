<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

#[Signature('evolayer:starter:prepare')]
#[Description('Regenerate Starter contracts, run reviewed quality gates, and record preparation evidence.')]
final class PrepareEvoLayerProfileCommand extends Command
{
    public function handle(): int
    {
        $commands = [
            [PHP_BINARY, 'artisan', 'config:clear', '--no-interaction'],
            [PHP_BINARY, 'artisan', 'route:clear', '--no-interaction'],
            [PHP_BINARY, 'artisan', 'wayfinder:generate', '--with-form', '--no-interaction'],
            [PHP_BINARY, 'artisan', 'evolayer:ontology:compile', '--no-erd', '--no-interaction'],
            ['npm', 'run', 'profile:quality'],
            [PHP_BINARY, 'artisan', 'evolayer:starter:record-preparation', '--internal', '--no-interaction'],
        ];

        foreach ($commands as $command) {
            $process = new Process($command, base_path());
            $process->setTimeout(null);
            $exitCode = $process->run(fn (string $type, string $output) => $this->output->write($output));

            if ($exitCode !== self::SUCCESS) {
                $this->components->error('Starter profile preparation failed.');

                return self::FAILURE;
            }
        }

        $this->components->info('Starter profile preparation is current.');

        return self::SUCCESS;
    }
}
