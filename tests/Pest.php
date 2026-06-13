<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Bind the application TestCase to the Feature and Unit suites so Pest's
| it()/test() functions boot the framework. Existing PHPUnit-style classes
| that already extend Tests\TestCase keep running unchanged.
|
*/

uses(TestCase::class)->in('Feature', 'Unit');
