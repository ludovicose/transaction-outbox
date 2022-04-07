<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Brokers;

use Closure;
use Illuminate\Support\Str;
use Ludovicose\TransactionOutbox\Contracts\MessageBroker;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class RabbitMQBroker implements MessageBroker
{
    private AbstractConnection $connection;
    private string $serviceName;
    private string $type;

    public function __construct(AbstractConnection $connection)
    {
        $this->connection  = $connection;
        $this->serviceName = config('transaction-outbox.serviceName', '');
        $this->type        = config('transaction-outbox.rabbitmq.default_type', 'fanout');
    }

    public function publish(string $channelName, string $body): void
    {
        $channel = $this->connection->channel();

        $exchange = $channelName;

        $channel->exchange_declare(
            $exchange,
            $this->type,
            false,
            true,
            false
        );

        $msg = new AMQPMessage($body);
        $channel->basic_publish($msg, $exchange);

        $channel->close();
        $this->connection->close();
    }

    public function subscribe($channels, Closure $closure): void
    {
        $channel = $this->connection->channel();

        foreach ($channels as $channelName) {
            $queue = $this->getQueue($channelName);

            $channel->queue_declare(
                $queue,
                false,
                true,
                false,
                false
            );

            $channel->exchange_declare(
                $channelName,
                $this->type,
                false,
                true,
                false
            );

            $channel->queue_bind($queue, $channelName);

            $channel->basic_consume(
                $queue,
                '',
                false,
                true,
                false,
                false,
                fn($msg) => $closure($msg->body)
            );
        }

        while ($channel->is_open()) {
            $channel->wait();
        }

        $channel->close();
        $this->connection->close();
    }

    private function getQueue($channelName): string
    {
        return "{$this->serviceName}.{$channelName}";
    }
}
