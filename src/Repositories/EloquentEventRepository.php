<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Ludovicose\TransactionOutbox\Contracts\EventDeleteRepository;
use Ludovicose\TransactionOutbox\Contracts\EventRepository as EventRepositoryContract;
use Ludovicose\TransactionOutbox\Contracts\RePublishEventRepository;
use Ludovicose\TransactionOutbox\Contracts\ReSendRequestRepository;
use Ludovicose\TransactionOutbox\Models\Event;

final class EloquentEventRepository implements EventRepositoryContract, RePublishEventRepository, ReSendRequestRepository, EventDeleteRepository
{
    public function persist(Event $event): void
    {
        $event->saveOrFail();
    }

    public function findBy(string $id, string $type): ?Event
    {
        return Event::where('event_id', $id)
            ->where('type', $type)->first();
    }

    public function getNotPublishEventBy(string $startDate, string $endDate): Collection
    {
        return Event::where('type', Event::TYPE_PUBLISH)
            ->whereNull('success_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    }

    public function getNotSendRequestBy(string $startDate, string $endDate): Collection
    {
        return Event::where('type', Event::TYPE_REQUEST)
            ->whereNull('success_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    }

    public function deleteLastEvent(int $day): void
    {
        $date = Carbon::now()->subDays($day);

        Event::whereDate('created_at', '<', $date)
            ->delete();
    }
}
