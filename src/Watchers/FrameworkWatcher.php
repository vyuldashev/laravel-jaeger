<?php

namespace Vyuldashev\LaravelJaeger\Watchers;

use Jaeger\Span;
use Vyuldashev\LaravelJaeger\Jaeger;

class FrameworkWatcher
{
    protected $jaeger;

    /** @var Span */
    protected $initialisationSpan;

    /** @var Span */
    protected $frameworkBootingSpan;

    /** @var Span */
    protected $frameworkRunningSpan;

    public function __construct(Jaeger $jaeger)
    {
        $this->jaeger = $jaeger;
    }

    public function register(): void
    {
        app()->booting(function () {
            $this->getInitialisationSpan()->finish();

            $this->getFrameworkBootingSpan();
            $this->getFrameworkRunningSpan();
        });

        app()->booted(function () {
            $this->getFrameworkBootingSpan()->finish();
        });

        app()->terminating(function () {
            $this->getFrameworkRunningSpan()->finish();
            $this->jaeger->getRootSpan()->finish();

            if (config('jaeger.enabled')) {
                $this->jaeger->client()->flush();
            }
        });
    }

    protected function getInitialisationSpan()
    {
        if ($this->initialisationSpan) {
            return $this->initialisationSpan;
        }

        $initialisationSpan = $this->jaeger->client()->startSpan('Application initialisation.', ['child_of' => $this->jaeger->getRootSpan()]);

        if (defined('LARAVEL_START')) {
            $initialisationSpan->startTime = (int)(LARAVEL_START * 1000000);
        }

        return $this->initialisationSpan = $initialisationSpan;
    }

    protected function getFrameworkBootingSpan()
    {
        if ($this->frameworkBootingSpan) {
            return $this->frameworkBootingSpan;
        }

        return $this->frameworkBootingSpan = $this->jaeger->client()->startSpan('Framework booting.', ['child_of' => $this->jaeger->getRootSpan()]);
    }

    protected function getFrameworkRunningSpan()
    {
        if ($this->frameworkRunningSpan) {
            return $this->frameworkRunningSpan;
        }

        return $this->frameworkRunningSpan = $this->jaeger->client()->startSpan('Framework running.', ['child_of' => $this->jaeger->getRootSpan()]);
    }
}
