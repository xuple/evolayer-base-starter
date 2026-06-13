<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('home page returns a successful response', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('"component":"evolayer\/about"', false);
});
