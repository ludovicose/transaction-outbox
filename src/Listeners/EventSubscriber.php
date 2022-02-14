<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Listeners;

use Ludovicose\TransactionOutbox\Commands\CreatePublishEventCommand;
use Ludovicose\TransactionOutbox\Contracts\ShouldBePublish;

final class EventSubscriber
{
    public function subscribe($events)
    {
        $events->listen('*', static::class . '@handle');
    }

    public function handle(string $eventName, $payload): void
    {
        if (!$this->shouldBePublish($eventName)) {
            return;
        }

        dispatch(new CreatePublishEventCommand($payload[0]));
    }

    private function shouldBePublish($event): bool
    {
        if (!class_exists($event)) {
            return false;
        }

        return is_subclass_of($event, ShouldBePublish::class);
    }
}
