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
        $data        = json_decode($command->message, true);
        $model       = new Event($data);
        $model->type = Event::TYPE_SUBSCRIBE;
        $model->setSuccessEvent();

        $this->eventRepository->persist($model);

        event($model->getChannel(), json_decode($model->getPayload()));
    }
}
