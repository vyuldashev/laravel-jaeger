<?php

return [

    'enabled' => env('JAEGER_ENABLED', false),

    'service_name' => env('JAEGER_SERVICE_NAME', env('APP_NAME', 'Laravel')),

    'agent' => [
        'host' => env('JAEGER_AGENT_HOST', 'jaeger'),
        'port' => env('JAEGER_AGENT_PORT', 6831),
    ],

    'watchers' => [
        Vyuldashev\LaravelJaeger\Watchers\CommandWatcher::class,
        Vyuldashev\LaravelJaeger\Watchers\FrameworkWatcher::class,
        Vyuldashev\LaravelJaeger\Watchers\QueryWatcher::class,
        Vyuldashev\LaravelJaeger\Watchers\RequestWatcher::class,
        Vyuldashev\LaravelJaeger\Watchers\ScheduleWatcher::class,
    ],

];
