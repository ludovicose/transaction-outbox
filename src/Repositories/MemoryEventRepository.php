<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Repositories;

use Ludovicose\TransactionOutbox\Contracts\EventDeleteRepository;
use Ludovicose\TransactionOutbox\Contracts\EventRepository as EventRepositoryContract;
use Ludovicose\TransactionOutbox\Models\Event;

final class MemoryEventRepository implements EventRepositoryContract, EventDeleteRepository
{
    public function persist(Event $event): void
    {
    }

    public function findBy(string $id, string $type): ?Event
    {
        return null;
    }

    public function deleteLastEvent(int $day): void
    {
    }
}
