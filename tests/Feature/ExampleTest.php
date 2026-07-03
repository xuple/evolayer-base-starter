<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public landing page returns a successful response', function () {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertSee('"component":"evolayer\/base"', false);
});
