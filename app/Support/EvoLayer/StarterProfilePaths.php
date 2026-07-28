<?php

namespace App\Support\EvoLayer;

final readonly class StarterProfilePaths
{
    public string $root;

    public function __construct(?string $root = null)
    {
        $this->root = $root ?? base_path();
    }

    public function path(string $relative): string
    {
        return $this->root.'/'.$relative;
    }

    public function registrationTemplate(): string
    {
        return $this->path('stubs/profiles/demo/resources/js/pages/auth/register.tsx');
    }

    public function preparationReceipt(): string
    {
        return $this->path('storage/framework/cache/data/evolayer-starter-preparation.json');
    }
}
