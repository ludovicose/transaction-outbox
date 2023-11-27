<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Brokers;

use Closure;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Arr;
use Ludovicose\TransactionOutbox\Contracts\MessageBroker;
use Ludovicose\TransactionOutbox\Exceptions\PublishException;
use Throwable;

class RedisBroker implements MessageBroker
{
    private string $serviceName;
    private string $errorQueue;

    public function __construct()
    {
        $this->serviceName = config('transaction-outbox.serviceName', '');
        $this->errorQueue  = config('transaction-outbox.rabbitmq.error_queue', 'errors');
    }

    protected function getInstance(): RedisManager
    {
        $config = $this->getConfig();

        return new RedisManager(app(), Arr::pull($config, 'client', 'phpredis'), $config);
    }

    public function publish(string $channelName, string $body): void
    {
        try {
            $instance = $this->getInstance();
            $instance->publish($channelName, $body);
        } catch (Throwable $e) {
            throw new PublishException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function subscribe($channels, Closure $closure): void
    {
        $instance = $this->getInstance();
        $instance->subscribe($channels, function ($message) use ($closure) {
            try {
                $closure($message);
            } catch (\Exception) {
                $this->addMessageToErrorQueue($message);
            }
        });
    }

    public function reSendErrorQueue(): void
    {
        $instance = $this->getInstance();

        $errorsItems = $instance->lrange($this->getErrorQueue(), 0, -1);

        foreach ($errorsItems as $errorItem) {
            $item = json_decode($errorItem, true);
            $this->publish($item['channel'], $errorItem);
        }

        $instance->del($this->getErrorQueue());
    }

    private function getErrorQueue(): string
    {
        return "{$this->serviceName}.{$this->errorQueue}";
    }

    protected function getConfig(): mixed
    {
        $config = config('database.redis', []);

        Arr::set($config, 'options.prefix', '');

        return $config;
    }

    private function addMessageToErrorQueue(string $message)
    {
        $instance = $this->getInstance();
        $instance->rpush($this->getErrorQueue(), $message);
    }
}
