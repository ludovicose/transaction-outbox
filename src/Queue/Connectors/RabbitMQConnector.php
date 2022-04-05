<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Queue\Connectors;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\Events\WorkerStopping;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Ludovicose\TransactionOutbox\Queue\QueueName;
use Ludovicose\TransactionOutbox\Queue\RabbitMQQueue;
use PhpAmqpLib\Connection\AbstractConnection;

final class RabbitMQConnector implements ConnectorInterface
{
    private Dispatcher $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function connect(array $config): Queue
    {
        $connection = app(AbstractConnection::class);

        $queue = $this->createQueue(
            Arr::get($config, 'worker', 'default'),
            $connection,
            $config['queue'],
            Arr::get($config, 'options.queue', [])
        );

        if (!$queue instanceof RabbitMQQueue) {
            throw new InvalidArgumentException('Invalid worker.');
        }

        $this->dispatcher->listen(WorkerStopping::class, static function () use ($queue): void {
            $queue->close();
        });

        return $queue;
    }


    protected function createQueue(string $worker, AbstractConnection $connection, string $queue, array $options = [])
    {
        switch ($worker) {
            case 'default':
                return new RabbitMQQueue($connection, $queue, $options);
            default:
                return new $worker($connection, $queue, $options);
        }
    }
}
