<?php

namespace Vyuldashev\LaravelJaeger;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Jaeger\Config;
use Vyuldashev\LaravelJaeger\Watchers\CommandWatcher;
use Vyuldashev\LaravelJaeger\Watchers\QueryWatcher;
use Vyuldashev\LaravelJaeger\Watchers\RequestWatcher;
use Vyuldashev\LaravelJaeger\Watchers\ScheduleWatcher;
use const Jaeger\Constants\PROPAGATOR_JAEGER;

class JaegerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/jaeger.php' => config_path('jaeger.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/jaeger.php', 'jaeger');

        $this->app->singleton(Jaeger::class, static function ($app) {
            $config = Config::getInstance();

            $config->gen128bit();
            $config::$propagator = PROPAGATOR_JAEGER;

            $client = $config->initTracer(
                config('jaeger.service_name'),
                config('jaeger.agent.host') . ':' . config('jaeger.agent.port')
            );

            return new Jaeger($app, $client);
        });

        foreach ([CommandWatcher::class, RequestWatcher::class, QueryWatcher::class, ScheduleWatcher::class] as $watcher) {
            resolve($watcher)->register();
        }
    }

    public function provides(): array
    {
        return [
            Jaeger::class,
        ];
    }
}
