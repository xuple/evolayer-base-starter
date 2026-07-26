<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public landing page returns a successful response', function () {
    $component = config('evolayer.base.examples.marketing_pages')
        ? 'evolayer\/base'
        : 'welcome';

    $this->get(route('welcome'))
        ->assertOk()
        ->assertSee('"component":"'.$component.'"', false);
});
