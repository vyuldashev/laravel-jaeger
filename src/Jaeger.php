<?php

namespace Vyuldashev\LaravelJaeger;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Jaeger\Span;
use OpenTracing\Tracer;
use const OpenTracing\Formats\TEXT_MAP;

class Jaeger
{
    protected $app;
    protected $tracer;

    /** @var Span|\OpenTracing\Span */
    protected $rootSpan;

    /** @var Span|\OpenTracing\Span */
    protected $initialisationSpan;

    /** @var Span|\OpenTracing\Span */
    protected $frameworkBootingSpan;

    /** @var Span|\OpenTracing\Span */
    protected $frameworkRunningSpan;

    public function __construct(Application $application, Tracer $tracer)
    {
        $this->app = $application;
        $this->tracer = $tracer;

        if (!$this->shouldTrace()) {
            return;
        }

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

            if (config('jaeger.enabled')) {
                $this->tracer->flush();
            }
        });
    }

    public function client(): Tracer
    {
        return $this->tracer;
    }

    public function getRootSpan()
    {
        if ($this->rootSpan) {
            return $this->rootSpan;
        }

        $headers = [];

        foreach (request()->headers->all() as $key => $value) {
            $headers[$key] = Arr::first($value);
        }

        $spanContext = $this->tracer->extract(TEXT_MAP, $headers);

        $rootSpan = $this->tracer->startSpan('root', ['child_of' => $spanContext]);

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

    public function getFrameworkRunningSpan()
    {
        if ($this->frameworkRunningSpan) {
            return $this->frameworkRunningSpan;
        }

        return $this->frameworkRunningSpan = $this->tracer->startSpan('Framework running.', ['child_of' => $this->getRootSpan()]);
    }

    public function inject(array $target): void
    {
        $this->tracer->inject($this->getRootSpan()->getContext(), TEXT_MAP, $target);
    }

    protected function getFrameworkBootingSpan()
    {
        if ($this->frameworkBootingSpan) {
            return $this->frameworkBootingSpan;
        }

        return $this->frameworkBootingSpan = $this->tracer->startSpan('Framework booting.', ['child_of' => $this->getRootSpan()]);
    }

    protected function getInitialisationSpan()
    {
        if ($this->initialisationSpan) {
            return $this->initialisationSpan;
        }

        $initialisationSpan = $this->tracer->startSpan('Application initialisation.', ['child_of' => $this->getRootSpan()]);

        if (defined('LARAVEL_START')) {
            $initialisationSpan->startTime = (int)(LARAVEL_START * 1000000);
        }

        return $this->initialisationSpan = $initialisationSpan;
    }

    protected function shouldTrace(): bool
    {
        if (!$this->app->runningInConsole()) {
            return true;
        }

        $traceableCommands = [
            // 'migrate',
            'migrate:rollback',
            'migrate:fresh',
            // 'migrate:refresh',
            'migrate:reset',
            'migrate:install',
            'package:discover',
            'queue:listen',
            'queue:work',
            'horizon',
            'horizon:work',
            'horizon:supervisor',
        ];

        return !in_array($_SERVER['argv'][1] ?? null, $traceableCommands, true);
    }
}
