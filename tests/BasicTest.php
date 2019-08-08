<?php

namespace Vyuldashev\LaravelJaeger\Tests;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use Vyuldashev\LaravelJaeger\JaegerServiceProvider;

class BasicTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/users', function () {
            return [];
        });
    }

    public function test(): void
    {
        $this->getJson('/users')->dump();
    }

    protected function getPackageProviders($app): array
    {
        return [JaegerServiceProvider::class];
    }
}
