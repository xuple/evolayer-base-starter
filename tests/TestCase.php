<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessExample(string $example, ?string $message = null): void
    {
        if (! config("evolayer.base.examples.{$example}")) {
            $this->markTestSkipped($message ?? "EvoLayer example [{$example}] is not enabled.");
        }
    }

    protected function skipUnlessFeature(string $feature, ?string $message = null): void
    {
        if (! config("evolayer.base.features.{$feature}")) {
            $this->markTestSkipped($message ?? "EvoLayer feature [{$feature}] is not enabled.");
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
