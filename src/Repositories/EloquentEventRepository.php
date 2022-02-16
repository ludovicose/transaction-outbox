<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Repositories;

use Ludovicose\TransactionOutbox\Contracts\EventRepository as EventRepositoryContract;
use Ludovicose\TransactionOutbox\Models\Event;

final class EloquentEventRepository implements EventRepositoryContract
{
    public function persist(Event $event): void
    {
        $event->saveOrFail();
    }

    public function findBy(string $id, string $type): Event
    {
        return Event::where('event_id', $id)
            ->where('type', $type)->first();
    }
}
