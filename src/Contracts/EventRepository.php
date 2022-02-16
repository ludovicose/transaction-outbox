<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Contracts;

use Ludovicose\TransactionOutbox\Models\Event;

interface EventRepository
{
    public function persist(Event $event): void;

    public function findBy(string $id, string $type): ?Event;
}
