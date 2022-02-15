<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Repositories;

use Ludovicose\TransactionOutbox\Contracts\EventRepository as EventRepositoryContract;
use Ludovicose\TransactionOutbox\Models\Event;

final class EloquentEventRepository implements EventRepositoryContract
{
    public function persist(Event $event): void
    {

    }

    public function findById(string $id): Event
    {
        // TODO: Implement findById() method.
    }
}
