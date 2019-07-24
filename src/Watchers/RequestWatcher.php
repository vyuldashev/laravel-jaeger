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
//            logger('RequestHandled');

            $rootSpan = $this->jaeger->getRootSpan();
            $rootSpan->overwriteOperationName($event->request->getRequestUri());
            $rootSpan->setTag('http.scheme', $event->request->getScheme());
            $rootSpan->setTag('http.host', $event->request->getHost());
            $rootSpan->setTag('http.path', optional($event->request->route())->uri() ?? $event->request->getPathInfo());
            $rootSpan->setTag('http.method', $event->request->getMethod());
            $rootSpan->setTag('http.status_code', (string)$event->response->getStatusCode());
            $rootSpan->setTag('http.error', $event->response->isSuccessful() ? 'false' : 'true');

            $this->jaeger->setRootSpan($rootSpan);
        });
    }
}
