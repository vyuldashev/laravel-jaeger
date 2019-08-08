<?php

namespace Vyuldashev\LaravelJaeger;

use Illuminate\Support\ServiceProvider;
use Jaeger\Config;
use const Jaeger\Constants\PROPAGATOR_JAEGER;

class JaegerServiceProvider extends ServiceProvider
{
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

        if (config('jaeger.enabled')) {
            foreach (config('jaeger.watchers', []) as $watcher) {
                resolve($watcher)->register();
            }
        }
    }
}
