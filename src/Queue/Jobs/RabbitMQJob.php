<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Queue\Jobs;

use Illuminate\Contracts\Container\Container;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Support\Arr;
use Ludovicose\TransactionOutbox\Queue\RabbitMQQueue;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

final class RabbitMQJob extends Job implements JobContract
{
    protected RabbitMQQueue $rabbitmq;

    protected AMQPMessage $message;

    protected array $decoded;

    public function __construct(
        Container $container,
        RabbitMQQueue $rabbitmq,
        AMQPMessage $message,
        string $connectionName,
        string $queue
    ) {
        $this->container      = $container;
        $this->rabbitmq       = $rabbitmq;
        $this->message        = $message;
        $this->connectionName = $connectionName;
        $this->queue          = $queue;
        $this->decoded        = $this->payload();
    }


    public function getJobId()
    {
        return $this->decoded['id'] ?? null;
    }


    public function getRawBody(): string
    {
        return $this->message->getBody();
    }


    public function attempts(): int
    {
        if (!$data = $this->getRabbitMQMessageHeaders()) {
            return 1;
        }

        $laravelAttempts = (int)Arr::get($data, 'laravel.attempts', 0);

        return $laravelAttempts + 1;
    }


    public function markAsFailed(): void
    {
        parent::markAsFailed();

        // We must tel rabbitMQ this Job is failed
        // The message must be rejected when the Job marked as failed, in case rabbitMQ wants to do some extra magic.
        // like: Death lettering the message to an other exchange/routing-key.
        $this->rabbitmq->reject($this);
    }

    public function delete(): void
    {
        parent::delete();

        // When delete is called and the Job was not failed, the message must be acknowledged.
        // This is because this is a controlled call by a developer. So the message was handled correct.
        if (!$this->failed) {
            $this->rabbitmq->ack($this);
        }
    }


    public function release($delay = 0): void
    {
        parent::release();

        // Always create a new message when this Job is released
        $this->rabbitmq->laterRaw($delay, $this->message->getBody(), $this->queue, $this->attempts());

        // Releasing a Job means the message was failed to process.
        // Because this Job message is always recreated and pushed as new message, this Job message is correctly handled.
        // We must tell rabbitMQ this job message can be removed by acknowledging the message.
        $this->rabbitmq->ack($this);
    }

    /**
     * Get the underlying RabbitMQ connection.
     *
     * @return RabbitMQQueue
     */
    public function getRabbitMQ(): RabbitMQQueue
    {
        return $this->rabbitmq;
    }

    /**
     * Get the underlying RabbitMQ message.
     *
     * @return AMQPMessage
     */
    public function getRabbitMQMessage(): AMQPMessage
    {
        return $this->message;
    }

    /**
     * Get the headers from the rabbitMQ message.
     *
     * @return array|null
     */
    protected function getRabbitMQMessageHeaders(): ?array
    {
        /** @var AMQPTable|null $headers */
        if (!$headers = Arr::get($this->message->get_properties(), 'application_headers')) {
            return null;
        }

        return $headers->getNativeData();
    }
}
