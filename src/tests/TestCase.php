<?php

namespace Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configure morph mapping for spatie/laravel-permission during tests
        Relation::enforceMorphMap([
            'user' => 'App\\Models\\User',
        ]);
    }
}
