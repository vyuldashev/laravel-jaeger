<?php

namespace Vyuldashev\LaravelJaeger\Watchers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Vyuldashev\LaravelJaeger\Jaeger;

class ScheduleWatcher
{
    protected $jaeger;

    public function __construct(Jaeger $jaeger)
    {
        $this->jaeger = $jaeger;
    }

    public function register(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if ($event->command !== 'schedule:run' && $event->command !== 'schedule:finish') {
                return;
            }

            $rootSpan = $this->jaeger->getRootSpan();
            $rootSpan->overwriteOperationName($event->command);
            $rootSpan->setTag('console.arguments', json_encode($event->input->getArguments(), JSON_UNESCAPED_UNICODE));
            $rootSpan->setTag('console.options', json_encode($event->input->getOptions(), JSON_UNESCAPED_UNICODE));

            $this->jaeger->setRootSpan($rootSpan);

            collect(app(Schedule::class)->events())->each(function ($event) {
                /** @var CallbackEvent|\Illuminate\Console\Scheduling\Event $event */
                $eventSpan = $this->jaeger->client()->startSpan(
                    $event instanceof CallbackEvent ? 'Closure' : $event->command,
                    ['child_of' => $this->jaeger->getFrameworkRunningSpan()]
                );

                $eventSpan->setTag('command.description', $event->description);
                $eventSpan->setTag('command.expression', $event->expression);
                $eventSpan->setTag('command.timezone', $event->timezone);
                $eventSpan->setTag('command.user', $event->user);

                $event->then(static function () use ($eventSpan) {
                    $eventSpan->finish();
                });
            });
        });
    }
}
