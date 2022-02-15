<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Handlers;

use Ludovicose\TransactionOutbox\Commands\SetSuccessEventCommand;
use Ludovicose\TransactionOutbox\Contracts\EventRepository;

final class SetSuccessEventHandler
{
    public function __construct(public EventRepository $eventRepository)
    {
    }

    public function handle(SetSuccessEventCommand $command)
    {
        $event = $this->eventRepository->findByEventId($command->id);
        $event->setSuccessEvent();
        $this->eventRepository->persist($event);
    }
}
