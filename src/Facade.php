<?php

namespace Vyuldashev\LaravelJaeger;

use Illuminate\Support\Facades\Facade as BaseFacade;

class Facade extends BaseFacade
{
    protected static function getFacadeAccessor(): string
    {
        return Jaeger::class;
    }
}
