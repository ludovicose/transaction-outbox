<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Listeners;

use Ludovicose\TransactionOutbox\Commands\SetSuccessEventCommand;
use Ludovicose\TransactionOutbox\Events\EventPublished;
use Ludovicose\TransactionOutbox\Models\Event;

final class EventPublishSuccessfullyListener
{
    public function handle(EventPublished $event)
    {
        dispatch(new SetSuccessEventCommand($event->id, Event::TYPE_PUBLISH));
    }
}
