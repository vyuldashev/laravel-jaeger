<?php

namespace Vyuldashev\LaravelJaeger\Watchers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Event;
use Vyuldashev\LaravelJaeger\Jaeger;

class CommandWatcher
{
    protected $jaeger;

    public function __construct(Jaeger $jaeger)
    {
        $this->jaeger = $jaeger;
    }

    public function register(): void
    {
        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            if (in_array($event->command, ['schedule:run', 'schedule:finish', 'package:discover'], true)) {
                return;
            }

            $command = $event->command ?? $event->input->getArguments()['command'] ?? 'default';

            $rootSpan = $this->jaeger->getRootSpan();
            $rootSpan->overwriteOperationName($command);
            $rootSpan->setTag('console.arguments', json_encode($event->input->getArguments(), JSON_UNESCAPED_UNICODE));
            $rootSpan->setTag('console.options', json_encode($event->input->getOptions(), JSON_UNESCAPED_UNICODE));
            $rootSpan->setTag('console.exit_code', (string)$event->exitCode);

            $this->jaeger->setRootSpan($rootSpan);
        });
    }
}
