<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Brokers;

use Closure;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Ludovicose\TransactionOutbox\Contracts\MessageBroker;
use Ludovicose\TransactionOutbox\Exceptions\PublishException;
use Throwable;

class RedisBroker implements MessageBroker
{
    protected function getInstance(string $prefix = ''): RedisManager
    {
        $config = $this->getConfig($prefix);

        return new RedisManager(app(), Arr::pull($config, 'client', 'phpredis'), $config);
    }

    public function publish(string $channelName, string $body): void
    {
        try {
            $serviceName = config('transaction-outbox.serviceName', '');
            $service     = $this->getInstance($serviceName);
            $service->publish($channelName, $body);
        } catch (Throwable $e) {
            throw new PublishException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function subscribe($channels, Closure $closure): void
    {
        $service = $this->getInstance();
        $service->subscribe($channels, $closure);
    }


    protected function getConfig(string $prefix = ''): mixed
    {
        $config = config('database.redis', []);

        if ($prefix) {
            $prefix = Str::of($prefix)->append('.');
        }

        Arr::set($config, 'options.prefix', $prefix);

        return $config;
    }
}
