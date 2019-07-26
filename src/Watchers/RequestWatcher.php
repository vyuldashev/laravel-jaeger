<?php

namespace Vyuldashev\LaravelJaeger\Watchers;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Event;
use Vyuldashev\LaravelJaeger\Jaeger;

class RequestWatcher
{
    protected $jaeger;

    public function __construct(Jaeger $jaeger)
    {
        $this->jaeger = $jaeger;
    }

    public function register(): void
    {
        Event::listen(RequestHandled::class, function (RequestHandled $event) {
            $rootSpan = $this->jaeger->getRootSpan();
            $rootSpan->overwriteOperationName(optional($event->request->route())->uri() ?? $event->request->getPathInfo());
            $rootSpan->setTag('http.scheme', $event->request->getScheme());
            $rootSpan->setTag('http.host', $event->request->getHost());
            $rootSpan->setTag('http.path', str_replace($event->request->root(), '', $event->request->fullUrl()) ?: '/');
            $rootSpan->setTag('http.method', $event->request->method());
            $rootSpan->setTag('http.status_code', (string)$event->response->getStatusCode());
            $rootSpan->setTag('http.error', $event->response->isSuccessful() ? 'false' : 'true');
            $rootSpan->setTag('laravel.controller_action', optional($event->request->route())->getActionName());

            $this->jaeger->setRootSpan($rootSpan);
        });
    }
}
