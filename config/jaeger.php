<?php

return [

    'enabled' => env('JAEGER_ENABLED', true),

    'service_name' => env('JAEGER_SERVICE_NAME', env('APP_NAME')),

    'agent' => [
        'host' => env('JAEGER_AGENT_HOST', 'jaeger'),
        'port' => env('JAEGER_AGENT_PORT', 6831),
    ],

];
