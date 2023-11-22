<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Brokers;

use Closure;
use Ludovicose\TransactionOutbox\Contracts\MessageBroker;
use Ludovicose\TransactionOutbox\Exceptions\TimeoutException;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

final class RabbitMQBroker implements MessageBroker
{
    private AbstractConnection $connection;
    private string $serviceName;
    private string $type;
    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    private int $timeout;

    public function __construct(AbstractConnection $connection)
    {
        $this->connection  = $connection;
        $this->serviceName = config('transaction-outbox.serviceName', '');
        $this->type        = config('transaction-outbox.rabbitmq.default_type', 'fanout');
        $this->timeout     = config('transaction-outbox.rabbitmq.timeout', 25);
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
        do {
            try {
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
                        function (AMQPMessage $msg) use ($closure) {
                            $closure($msg->body);
                        }
                    );
                }

                while ($channel->is_open()) {
                    try {
                        $channel->wait(null, false, $this->timeout * 60);
                    } catch (AMQPTimeoutException $exception) {
                        $channel->close();
                        $this->connection->close();
                        throw new TimeoutException($exception->getMessage());
                    }
                }

                $channel->close();
                $this->connection->close();
                exit(0);

            } catch (TimeoutException $e) {
                sleep(5);
            }
        } while (true);
    }

    private function getQueue($channelName): string
    {
        return "{$this->serviceName}.{$channelName}";
    }
}
