<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Listeners;

use Ludovicose\TransactionOutbox\Commands\PublishEventToBrokerCommand;
use Ludovicose\TransactionOutbox\Events\PublishEventCreated;

final class PublishEventListener
{
    public function handle(PublishEventCreated $event)
    {
        dispatch(new PublishEventToBrokerCommand($event->model));
    }
}
