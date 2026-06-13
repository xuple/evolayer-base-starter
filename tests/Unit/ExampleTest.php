<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('true is true', function () {
    expect(true)->toBeTrue();
});
