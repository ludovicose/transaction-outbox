<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Handlers;

use Ludovicose\TransactionOutbox\Commands\SetSuccessEventCommand;
use Ludovicose\TransactionOutbox\Contracts\EventRepository;
use Ludovicose\TransactionOutbox\Models\Event;

final class SetSuccessEventHandler
{
    public function __construct(public EventRepository $eventRepository)
    {
    }

    public function handle(SetSuccessEventCommand $command)
    {
        $event = $this->eventRepository->findBy($command->id, $command->type);
        $event->setSuccessEvent();
        $this->eventRepository->persist($event);
    }
}
