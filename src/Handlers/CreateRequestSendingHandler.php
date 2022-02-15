<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Handlers;

use Ludovicose\TransactionOutbox\Commands\CreateRequestSendingCommand;
use Ludovicose\TransactionOutbox\Contracts\EventRepository;
use Ludovicose\TransactionOutbox\Models\Event;

final class CreateRequestSendingHandler
{
    public function __construct(public EventRepository $eventRepository)
    {
    }

    public function handle(CreateRequestSendingCommand $command)
    {
        $model           = new Event();
        $model->event_id = $command->requestId;
        $model->payload  = json_encode($command->data);
        $model->type     = Event::TYPE_REQUEST;
        $model->channel  = Event::DEFAULT_CHANNEL;

        $this->eventRepository->persist($model);
    }
}
