<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Handlers;

use Ludovicose\TransactionOutbox\Commands\PublishEventToBrokerCommand;
use Ludovicose\TransactionOutbox\Contracts\MessageBroker;

final class PublishEventToBrokerHandler
{
    private MessageBroker $messageBroker;

    public function __construct(MessageBroker $messageBroker)
    {
        $this->messageBroker = $messageBroker;
    }

    public function handle(PublishEventToBrokerCommand $command)
    {
        $this->messageBroker->publish($command->eventModel->getChannel(), $command->eventModel->getPayload());
    }
}
