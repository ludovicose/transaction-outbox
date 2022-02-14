<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Events;

use Ludovicose\TransactionOutbox\Models\Event;

final class PublishEventCreated
{
    public function __construct(public Event $model)
    {

    }
}
