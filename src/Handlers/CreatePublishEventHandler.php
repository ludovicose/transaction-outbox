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
    public function __construct(public EventRepository $eventRepository)
    {

    }

    public function handle(CreatePublishEventCommand $command)
    {
        $data = app(EventPublishSerializer::class)->serialize(clone $command->event);

        $model          = new Event();
        $model->payload = $data;
        $model->id      = Str::uuid()->toString();
        $model->channel = $command->event->getChannel();

        $this->eventRepository->persist($model);

        event(new PublishEventCreated($model));
    }
}
