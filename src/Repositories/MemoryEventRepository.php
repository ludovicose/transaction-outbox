<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Repositories;

use Ludovicose\TransactionOutbox\Contracts\EventRepository as EventRepositoryContract;
use Ludovicose\TransactionOutbox\Models\Event;

final class MemoryEventRepository implements EventRepositoryContract
{
    public function persist(Event $event): void
    {
    }

    public function findByEventId(string $id): Event
    {
        return new Event();
    }
}
