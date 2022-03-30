<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Handlers;

use Illuminate\Support\Str;
use Ludovicose\TransactionOutbox\Commands\CreatePublishEventCommand;
use Ludovicose\TransactionOutbox\Contracts\EventPublishSerializer;
use Ludovicose\TransactionOutbox\Contracts\EventRepository;
use Ludovicose\TransactionOutbox\Events\PublishEventCreated;
use Ludovicose\TransactionOutbox\Models\Event;

final class CreatePublishEventHandler
{
    public string $serviceName;

    public function __construct(public EventRepository $eventRepository)
    {
        $this->serviceName = config('transaction-outbox.serviceName', '');
    }

    public function handle(CreatePublishEventCommand $command)
    {
        $data = app(EventPublishSerializer::class)->serialize(clone $command->event);

        $model           = new Event();
        $model->payload  = $data;
        $model->event_id = Str::uuid()->toString();
        $model->channel  = "{$this->serviceName}.{$command->event->getChannel()}";
        $model->type     = Event::TYPE_PUBLISH;

        $this->eventRepository->persist($model);

        event(new PublishEventCreated($model));
    }
}
