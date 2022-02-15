<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Handlers;

use Ludovicose\TransactionOutbox\Commands\PublishEventToBrokerCommand;
use Ludovicose\TransactionOutbox\Contracts\EventPublishSerializer;
use Ludovicose\TransactionOutbox\Contracts\MessageBroker;
use Ludovicose\TransactionOutbox\Events\EventPublished;

final class PublishEventToBrokerHandler
{
    private MessageBroker $messageBroker;

    public function __construct(MessageBroker $messageBroker)
    {
        $this->messageBroker = $messageBroker;
    }

    public function handle(PublishEventToBrokerCommand $command)
    {
        $this->messageBroker->publish($command->eventModel->getChannel(), $command->eventModel->toJson());

        event(new EventPublished($command->eventModel->getId()));
    }
}
