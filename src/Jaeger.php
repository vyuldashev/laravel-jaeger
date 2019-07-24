<?php

namespace Vyuldashev\LaravelJaeger;

use Illuminate\Contracts\Foundation\Application;
use Jaeger\Jaeger as Client;
use Jaeger\Span;

class Jaeger
{
    protected $app;
    protected $client;

    /** @var Span */
    protected $rootSpan;

    /** @var Span */
    protected $initialisationSpan;

    /** @var Span */
    protected $frameworkBootingSpan;

    /** @var Span */
    protected $frameworkRunningSpan;

    public function __construct(Application $application, Client $client)
    {
        $this->app = $application;
        $this->client = $client;

        $this->app->booting(function () {
            $this->getInitialisationSpan()->finish();

            $this->getFrameworkBootingSpan();
            $this->getFrameworkRunningSpan();
        });

        $this->app->booted(function () {
            $this->getFrameworkBootingSpan()->finish();
        });

        $this->app->terminating(function () {
            $this->getFrameworkRunningSpan()->finish();
            $this->getRootSpan()->finish();

            $this->client->flush();
        });
    }

    public function client(): Client
    {
        return $this->client;
    }

    public function getRootSpan(): Span
    {
        if ($this->rootSpan) {
            return $this->rootSpan;
        }

        $rootSpan = $this->client->startSpan('root');

        if (defined('LARAVEL_START')) {
            $rootSpan->startTime = (int)(LARAVEL_START * 1000000);
        }

        $rootSpan->setTag('type', $this->app->runningInConsole() ? 'console' : 'http');
        $rootSpan->setTag('laravel.version', $this->app->version());

        return $this->rootSpan = $rootSpan;
    }

    public function setRootSpan(Span $rootSpan): void
    {
        $this->rootSpan = $rootSpan;
    }

    public function getInitialisationSpan(): Span
    {
        if ($this->initialisationSpan) {
            return $this->initialisationSpan;
        }

        $initialisationSpan = $this->client->startSpan('Application initialisation.', ['child_of' => $this->getRootSpan()]);

        if (defined('LARAVEL_START')) {
            $initialisationSpan->startTime = (int)(LARAVEL_START * 1000000);
        }

        return $this->initialisationSpan = $initialisationSpan;
    }

    public function getFrameworkBootingSpan(): Span
    {
        if ($this->frameworkBootingSpan) {
            return $this->frameworkBootingSpan;
        }

        $frameworkBootingSpan = $this->client->startSpan('Framework booting.', ['child_of' => $this->getRootSpan()]);

        if (defined('LARAVEL_START')) {
            $frameworkBootingSpan->startTime = (int)(LARAVEL_START * 1000000);
        }

        return $this->frameworkBootingSpan = $frameworkBootingSpan;
    }

    public function getFrameworkRunningSpan(): Span
    {
        if ($this->frameworkRunningSpan) {
            return $this->frameworkRunningSpan;
        }

        $frameworkRunningSpan = $this->client->startSpan('Framework running.', ['child_of' => $this->getRootSpan()]);

        return $this->frameworkRunningSpan = $frameworkRunningSpan;
    }
}
