<?php

namespace Vyuldashev\LaravelJaeger\Tests;

use BlastCloud\Guzzler\UsesGuzzler;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Vyuldashev\LaravelJaeger\JaegerServiceProvider;
use Vyuldashev\LaravelJaeger\PsrHttpMessageMiddleware;

class PsrHttpMessageMiddlewareTest extends TestCase
{
    use UsesGuzzler;

    public function test(): void
    {
        $this->withoutExceptionHandling();

        $stack = $this->guzzler->getHandlerStack();
        $stack->push(PsrHttpMessageMiddleware::make());

        $stack->push(function (callable $handler) {
            return function (RequestInterface $request, $options) use ($handler) {
                /** @var Promise $promise */
                $promise = $handler($request, $options);
                return $promise->then(
                    function (ResponseInterface $response) use ($request) {
                        $this->assertArrayHasKey('UBER-TRACE-ID', $request->getHeaders());
                        $this->assertNotEmpty($request->getHeader('UBER-TRACE-ID'));
                        return $response;
                    }
                );
            };
        });

        $client = new Client([
            'handler' => $stack,
        ]);

        $this->guzzler->queueResponse(new Response(200, ['some-header' => 'value'], 'some body'));

        Route::get('/send-http-request', static function () use ($client) {
            return $client->get('/my-endpoint');
        });

        $this->getJson('/send-http-request')->assertOk();
    }

    protected function getPackageProviders($app): array
    {
        return [JaegerServiceProvider::class];
    }
}
