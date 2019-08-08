<?php

namespace Vyuldashev\LaravelJaeger;

use Psr\Http\Message\RequestInterface;

class PsrHttpMessageMiddleware
{
    public static function make(): callable
    {
        return static function (callable $handler) {
            return static function (RequestInterface $request, array $options) use ($handler) {
                /** @var Jaeger $jaeger */
                $jaeger = resolve(Jaeger::class);

                $headers = [];

                $jaeger->inject($headers);

                foreach ($headers as $key => $value) {
                    $request = $request->withHeader($key, $value);
                }

                return $handler($request, $options);
            };
        };
    }
}
