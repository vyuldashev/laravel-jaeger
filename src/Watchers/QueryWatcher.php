<?php

namespace Vyuldashev\LaravelJaeger\Watchers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
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
        DB::listen(function (QueryExecuted $query) {
            $querySpan = $this->jaeger->client()->startSpan(
                'DB Query',
                ['child_of' => $this->jaeger->getFrameworkRunningSpan()]
            );
            $querySpan->setTag('query.sql', $query->sql);
            $querySpan->setTag('query.bindings', json_encode($query->bindings, JSON_UNESCAPED_UNICODE));
            $querySpan->setTag('query.connection_name', $query->connectionName);
            $querySpan->duration = $query->time * 1000;
        });
    }
}
