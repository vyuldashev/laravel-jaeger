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

    public function __construct(Application $application, Tracer $tracer)
    {
        $this->app = $application;
        $this->tracer = $tracer;

        if (!$this->shouldTrace()) {
            return;
        }
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

    public function inject(array &$target): void
    {
        $this->tracer->inject($this->getRootSpan()->getContext(), TEXT_MAP, $target);
    }

    protected function shouldTrace(): bool
    {
        if (!$this->app->runningInConsole()) {
            return true;
        }

        $ignoredArtisanCommand = [
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

        if (in_array($_SERVER['argv'][1] ?? null, $ignoredArtisanCommand, true)) {
            return false;
        }

        return true;
    }
}
