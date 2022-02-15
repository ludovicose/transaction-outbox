<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Events;

final class EventPublished
{
    public function __construct(public string $id)
    {
    }
}
