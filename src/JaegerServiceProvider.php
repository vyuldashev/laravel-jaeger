<?php

namespace Vyuldashev\LaravelJaeger;

use Illuminate\Support\ServiceProvider;
use Jaeger\Config;
use Ramsey\Uuid\Uuid;
use Vyuldashev\LaravelJaeger\Watchers\CommandWatcher;
use Vyuldashev\LaravelJaeger\Watchers\QueryWatcher;
use Vyuldashev\LaravelJaeger\Watchers\RequestWatcher;
use Vyuldashev\LaravelJaeger\Watchers\ScheduleWatcher;
use const Jaeger\Constants\PROPAGATOR_JAEGER;

class JaegerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('jaeger.uuid', (string)Uuid::uuid4());

        $this->app->singleton(Jaeger::class, function ($app) {
            $config = Config::getInstance();
            $config->gen128bit();
            $config::$propagator = PROPAGATOR_JAEGER;

            $client = $config->initTracer(config('app.name'), 'jaeger:6831');

            return new Jaeger($app, $client);
        });

        foreach ([CommandWatcher::class, RequestWatcher::class, QueryWatcher::class, ScheduleWatcher::class] as $watcher) {
            resolve($watcher)->register();
        }
//
//        Event::listen('*', function ($event) {
//            $data = getmypid() . ' | ' . $event . PHP_EOL;
//
//            file_put_contents(base_path('events.json'), $data, FILE_APPEND);
//        });
    }
}
