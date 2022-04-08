<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Queue;

use ErrorException;
use Exception;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Ludovicose\TransactionOutbox\Queue\Jobs\RabbitMQJob;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMQQueue extends \Illuminate\Queue\Queue implements Queue
{
    protected AbstractConnection $connection;

    protected AMQPChannel $channel;

    protected string $default;

    protected array $exchanges = [];

    protected array $queues = [];

    protected array $boundQueues = [];

    protected ?RabbitMQJob $currentJob = null;

    protected array $options;

    public function __construct(
        AbstractConnection $connection,
        string             $default,
        array              $options = []
    )
    {
        $this->connection = $connection;
        $this->channel    = $connection->channel();
        $this->default    = $default;
        $this->options    = $options;
    }

    public function size($queue = null): int
    {
        $queue = $this->getQueue($queue);

        if (!$this->isQueueExists($queue)) {
            return 0;
        }

        $channel = $this->connection->channel();
        [, $size] = $channel->queue_declare($queue, true);
        $channel->close();

        return $size;
    }

    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $queue, $data), $queue, []);
    }


    public function pushRaw($payload, $queue = null, array $options = [])
    {
        [$destination, $exchange, $exchangeType, $attempts] = $this->publishProperties($queue, $options);

        $this->declareDestination($destination, $exchange, $exchangeType);

        [$message, $correlationId] = $this->createMessage($payload, $attempts);

        $this->channel->basic_publish($message, $exchange, $destination, true, false);

        return $correlationId;
    }


    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->laterRaw(
            $delay,
            $this->createPayload($job, $queue, $data),
            $queue
        );
    }


    public function laterRaw($delay, $payload, $queue = null, $attempts = 0)
    {
        $ttl = $this->secondsUntil($delay) * 1000;

        if ($ttl <= 0) {
            return $this->pushRaw($payload, $queue, ['delay' => $delay, 'attempts' => $attempts]);
        }

        $destination = $this->getQueue($queue) . '.delay.' . $ttl;

        $this->declareQueue($destination, true, false, $this->getDelayQueueArguments($this->getQueue($queue), $ttl));

        [$message, $correlationId] = $this->createMessage($payload, $attempts);

        $this->channel->basic_publish($message, null, $destination, true, false);

        return $correlationId;
    }

    public function bulk($jobs, $data = '', $queue = null): void
    {
        foreach ((array)$jobs as $job) {
            $this->bulkRaw($this->createPayload($job, $queue, $data), $queue, ['job' => $job]);
        }

        $this->channel->publish_batch();
    }

    public function bulkRaw(string $payload, $queue = null, array $options = [])
    {
        [$destination, $exchange, $exchangeType, $attempts] = $this->publishProperties($queue, $options);

        $this->declareDestination($destination, $exchange, $exchangeType);

        [$message, $correlationId] = $this->createMessage($payload, $attempts);

        $this->channel->batch_basic_publish($message, $exchange, $destination);

        return $correlationId;
    }


    public function pop($queue = null)
    {
        try {
            $queue = $this->getQueue($queue);

            $job = $this->getJobClass();

            /** @var AMQPMessage|null $message */
            if ($message = $this->channel->basic_get($queue)) {
                return $this->currentJob = new $job(
                    $this->container,
                    $this,
                    $message,
                    $this->connectionName,
                    $queue
                );
            }
        } catch (AMQPProtocolChannelException $exception) {
            // If there is not exchange or queue AMQP will throw exception with code 404
            // We need to catch it and return null
            if ($exception->amqp_reply_code === 404) {
                // Because of the channel exception the channel was closed and removed.
                // We have to open a new channel. Because else the worker(s) are stuck in a loop, without processing.
                $this->channel = $this->connection->channel();

                return null;
            }

            throw $exception;
        }

        return null;
    }


    public function getConnection(): AbstractConnection
    {
        return $this->connection;
    }


    public function getChannel(): AMQPChannel
    {
        return $this->channel;
    }


    public function getJobClass(): string
    {
        $job = Arr::get($this->options, 'job', RabbitMQJob::class);

        throw_if(
            !is_a($job, RabbitMQJob::class, true),
            Exception::class,
            sprintf('Class %s must extend: %s', $job, RabbitMQJob::class)
        );

        return $job;
    }


    public function getQueue($queue = null): string
    {
        $queue = $queue ?: $this->default;

        return QueueName::getName($queue);
    }


    public function isExchangeExists(string $exchange): bool
    {
        if ($this->isExchangeDeclared($exchange)) {
            return true;
        }

        try {
            $channel = $this->connection->channel();
            $channel->exchange_declare($exchange, '', true);
            $channel->close();

            $this->exchanges[] = $exchange;

            return true;
        } catch (AMQPProtocolChannelException $exception) {
            if ($exception->amqp_reply_code === 404) {
                return false;
            }

            throw $exception;
        }
    }


    public function declareExchange(
        string $name,
        string $type = AMQPExchangeType::DIRECT,
        bool   $durable = true,
        bool   $autoDelete = false,
        array  $arguments = []
    ): void
    {
        if ($this->isExchangeDeclared($name)) {
            return;
        }

        $this->channel->exchange_declare(
            $name,
            $type,
            false,
            $durable,
            $autoDelete,
            false,
            true,
            new AMQPTable($arguments)
        );
    }


    public function deleteExchange(string $name, bool $unused = false): void
    {
        if (!$this->isExchangeExists($name)) {
            return;
        }

        $idx = array_search($name, $this->exchanges);
        unset($this->exchanges[$idx]);

        $this->channel->exchange_delete(
            $name,
            $unused
        );
    }


    public function isQueueExists(string $name = null): bool
    {
        try {
            $channel = $this->connection->channel();
            $channel->queue_declare($this->getQueue($name), true);
            $channel->close();

            return true;
        } catch (AMQPProtocolChannelException $exception) {
            if ($exception->amqp_reply_code === 404) {
                return false;
            }

            throw $exception;
        }
    }


    public function declareQueue(
        string $name,
        bool   $durable = true,
        bool   $autoDelete = false,
        array  $arguments = []
    ): void
    {
        if ($this->isQueueDeclared($name)) {
            return;
        }

        $this->channel->queue_declare(
            $name,
            false,
            $durable,
            false,
            $autoDelete,
            false,
            new AMQPTable($arguments)
        );
    }


    public function deleteQueue(string $name, bool $if_unused = false, bool $if_empty = false): void
    {
        if (!$this->isQueueExists($name)) {
            return;
        }

        $this->channel->queue_delete($name, $if_unused, $if_empty);
    }


    public function bindQueue(string $queue, string $exchange, string $routingKey = ''): void
    {
        if (
            in_array(
                implode('', compact('queue', 'exchange', 'routingKey')),
                $this->boundQueues,
                true
            )
        ) {
            return;
        }

        $this->channel->queue_bind($queue, $exchange, $routingKey);
    }


    public function purge(string $queue = null): void
    {
        // create a temporary channel, so the main channel will not be closed on exception
        $channel = $this->connection->channel();
        $channel->queue_purge($this->getQueue($queue));
        $channel->close();
    }


    public function ack(RabbitMQJob $job): void
    {
        $this->channel->basic_ack($job->getRabbitMQMessage()->getDeliveryTag());
    }


    public function reject(RabbitMQJob $job, bool $requeue = false): void
    {
        $this->channel->basic_reject($job->getRabbitMQMessage()->getDeliveryTag(), $requeue);
    }

    protected function createMessage($payload, int $attempts = 0): array
    {
        $properties = [
            'content_type'  => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ];

        $currentPayload = json_decode($payload, true, 512);
        if ($correlationId = $currentPayload['id'] ?? null) {
            $properties['correlation_id'] = $correlationId;
        }

        if ($this->isPrioritizeDelayed()) {
            $properties['priority'] = $attempts;
        }

        if (isset($currentPayload['data']['command'])) {
            $commandData = unserialize($currentPayload['data']['command']);
            if (property_exists($commandData, 'priority')) {
                $properties['priority'] = $commandData->priority;
            }
        }

        $message = new AMQPMessage($payload, $properties);

        $message->set('application_headers', new AMQPTable([
            'laravel' => [
                'attempts' => $attempts,
            ],
        ]));

        return [
            $message,
            $correlationId,
        ];
    }


    protected function createPayloadArray($job, $queue, $data = ''): array
    {
        return array_merge(parent::createPayloadArray($job, $queue, $data), [
            'id' => $this->getRandomId(),
        ]);
    }

    protected function getRandomId(): string
    {
        return Str::uuid()->toString();
    }

    public function close(): void
    {
        if ($this->currentJob && !$this->currentJob->isDeletedOrReleased()) {
            $this->reject($this->currentJob, true);
        }

        try {
            $this->connection->close();
        } catch (ErrorException $exception) {
            // Ignore the exception
        }
    }

    protected function getQueueArguments(string $destination): array
    {
        $arguments = [];

        if ($this->isPrioritizeDelayed() && !$this->isQuorum()) {
            $arguments['x-max-priority'] = $this->getQueueMaxPriority();
        }

        if ($this->isRerouteFailed()) {
            $arguments['x-dead-letter-exchange']    = $this->getFailedExchange() ?? '';
            $arguments['x-dead-letter-routing-key'] = $this->getFailedRoutingKey($destination);
        }

        if ($this->isQuorum()) {
            $arguments['x-queue-type'] = 'quorum';
        }

        return $arguments;
    }


    protected function getDelayQueueArguments(string $destination, int $ttl): array
    {
        return [
            'x-dead-letter-exchange'    => $this->getExchange() ?? '',
            'x-dead-letter-routing-key' => $this->getRoutingKey($destination),
            'x-message-ttl'             => $ttl,
            'x-expires'                 => $ttl * 2,
        ];
    }

    protected function isPrioritizeDelayed(): bool
    {
        return (bool)(Arr::get($this->options, 'prioritize_delayed') ?: false);
    }


    protected function getQueueMaxPriority(): int
    {
        return (int)(Arr::get($this->options, 'queue_max_priority') ?: 2);
    }


    protected function getExchange(string $exchange = null): ?string
    {
        return $exchange ?: Arr::get($this->options, 'exchange') ?: null;
    }


    protected function getRoutingKey(string $destination): string
    {
        return ltrim(sprintf(Arr::get($this->options, 'exchange_routing_key') ?: '%s', $destination), '.');
    }


    protected function getExchangeType(?string $type = null): string
    {
        return @constant(AMQPExchangeType::class . '::' . Str::upper($type ?: Arr::get(
                $this->options,
                'exchange_type'
            ) ?: 'direct')) ?: AMQPExchangeType::DIRECT;
    }


    protected function isRerouteFailed(): bool
    {
        return (bool)(Arr::get($this->options, 'reroute_failed') ?: false);
    }


    protected function isQuorum(): bool
    {
        return (bool)(Arr::get($this->options, 'quorum') ?: false);
    }


    protected function getFailedExchange(string $exchange = null): ?string
    {
        return $exchange ?: Arr::get($this->options, 'failed_exchange') ?: null;
    }

    protected function getFailedRoutingKey(string $destination): string
    {
        return ltrim(sprintf(Arr::get($this->options, 'failed_routing_key') ?: '%s.failed', $destination), '.');
    }


    protected function isExchangeDeclared(string $name): bool
    {
        return in_array($name, $this->exchanges, true);
    }


    protected function isQueueDeclared(string $name): bool
    {
        return in_array($name, $this->queues, true);
    }


    protected function declareDestination(
        string  $destination,
        ?string $exchange = null,
        string  $exchangeType = AMQPExchangeType::DIRECT
    ): void
    {
        if ($exchange && !$this->isExchangeExists($exchange)) {
            $this->declareExchange($exchange, $exchangeType);
        }

        if ($exchange) {
            return;
        }

        if ($this->isQueueExists($destination)) {
            return;
        }

        $this->declareQueue($destination, true, false, $this->getQueueArguments($destination));
    }

    protected function publishProperties($queue, array $options = []): array
    {
        $queue    = $this->getQueue($queue);
        $attempts = Arr::get($options, 'attempts') ?: 0;

        $destination  = $this->getRoutingKey($queue);
        $exchange     = $this->getExchange(Arr::get($options, 'exchange'));
        $exchangeType = $this->getExchangeType(Arr::get($options, 'exchange_type'));

        return [$destination, $exchange, $exchangeType, $attempts];
    }
}
