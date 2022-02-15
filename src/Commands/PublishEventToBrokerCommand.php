<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Commands;

use Ludovicose\TransactionOutbox\Models\Event;

final class PublishEventToBrokerCommand
{
    public function __construct(public Event $eventModel)
    {
    }
}
