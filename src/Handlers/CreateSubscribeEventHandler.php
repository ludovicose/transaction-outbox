<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Handlers;

use Ludovicose\TransactionOutbox\Commands\CreateSubscribeEventCommand;
use Ludovicose\TransactionOutbox\Contracts\EventRepository;
use Ludovicose\TransactionOutbox\Models\Event;

final class CreateSubscribeEventHandler
{
    public function __construct(public EventRepository $eventRepository)
    {
    }

    public function handle(CreateSubscribeEventCommand $command)
    {
        $data  = json_decode($command->message, true);
        $event = new Event($data);

        if ($this->hasEvent($event)) {
            return;
        }

        $event->type = Event::TYPE_SUBSCRIBE;
        $event->setSuccessEvent();

        $this->eventRepository->persist($event);

        event($event->getChannel(), json_decode($event->getPayload()));
    }

    private function hasEvent(Event $event): ?Event
    {
        return $this->eventRepository->findBy($event->event_id, Event::TYPE_SUBSCRIBE);
    }
}
