<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Brokers;

use Closure;
use Exception;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Ludovicose\TransactionOutbox\Contracts\MessageBroker;
use Ludovicose\TransactionOutbox\Exceptions\TimeoutException;
use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPHeartbeatMissedException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

final class RabbitMQBroker implements MessageBroker
{
    public AbstractConnection $connection;
    private string $serviceName;
    private string $type;
    /**
     * @var Repository|Application|mixed
     */
    private int $timeout;
    /**
     * @var Repository|Application|mixed
     */
    private string $errorQueue;

    public function __construct(AbstractConnection $connection)
    {
        $this->connection  = $connection;
        $this->serviceName = config('transaction-outbox.serviceName', '');
        $this->type        = config('transaction-outbox.rabbitmq.default_type', 'fanout');
        $this->timeout     = config('transaction-outbox.rabbitmq.timeout', 25);
        $this->errorQueue  = config('transaction-outbox.rabbitmq.error_queue', 'errors');
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
                        function (AMQPMessage $msg) use ($closure, $channel, $queue) {
                            try {
                                $closure($msg->body);
                            } catch (Exception) {
                                $this->addMessageToErrorQueue($channel, $msg);
                            }
                        }
                    );
                }

                while ($channel->is_open()) {
                    try {
                        $channel->wait(null, false, $this->timeout * 60);
                    } catch (AMQPTimeoutException | AMQPHeartbeatMissedException $exception) {
                        $channel->close();
                        $this->connection->close();
                        throw new TimeoutException($exception->getMessage());
                    }
                }

                $channel->close();
                $this->connection->close();
                exit(0);
            } catch (TimeoutException) {
                sleep(5);
            }
        } while (true);
    }

    public function reSendErrorQueue(): void
    {
        /** @var AMQPChannel $channel */
        $channel = $this->connection->channel();
        $channel->queue_declare($this->getErrorQueue(), false, true, false, false);

        /** @var AMQPMessage $message */
        while ($message = $channel->basic_get($this->getErrorQueue())) {
            $data        = json_decode($message->body);
            $channelName = $data->channel;
            $channel->basic_publish($message, $channelName);
            $channel->basic_ack($message->getDeliveryTag());
        }

        $channel->close();
        $this->connection->close();
        ;
    }

    private function getQueue($channelName): string
    {
        return "{$this->serviceName}.{$channelName}";
    }

    private function getErrorQueue(): string
    {
        return "{$this->serviceName}.{$this->errorQueue}";
    }

    private function addMessageToErrorQueue(AMQPChannel|AbstractChannel $channel, AMQPMessage $message): void
    {
        $errorQueue = $this->getErrorQueue();
        $channel->queue_declare($errorQueue, false, true, false, false);
        $channel->basic_publish($message, '', $errorQueue);
    }
}
