<?php

namespace Vyuldashev\LaravelJaeger\Watchers;

use Illuminate\Database\Events\QueryExecuted;
use Vyuldashev\LaravelJaeger\Jaeger;

class QueryWatcher
{
    protected $jaeger;

    public function __construct(Jaeger $jaeger)
    {
        $this->jaeger = $jaeger;
    }

    public function register(): void
    {
        app('events')->listen(QueryExecuted::class, function (QueryExecuted $event) {
            $querySpan = $this->jaeger->client()->startSpan(
                'DB Query',
                ['child_of' => $this->jaeger->getFrameworkRunningSpan()]
            );
            $querySpan->setTag('query.sql', $event->sql);
            $querySpan->setTag('query.bindings', json_encode($event->bindings, JSON_UNESCAPED_UNICODE));
            $querySpan->setTag('query.connection_name', $event->connectionName);
            $querySpan->duration = $event->time * 1000;
        });
    }
}
