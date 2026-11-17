<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The frontend build (public/build) is not in git: pages must render without a manifest
        $this->withoutVite();
    }
}
