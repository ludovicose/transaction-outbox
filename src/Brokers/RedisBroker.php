<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Brokers;

use Closure;
use Illuminate\Redis\RedisManager;
use Ludovicose\TransactionOutbox\Contracts\MessageBroker;
use Ludovicose\TransactionOutbox\Exceptions\PublishException;
use Throwable;

class RedisBroker implements MessageBroker
{
    private RedisManager $service;

    public function __construct(public RedisManager $redisManager)
    {
        $this->service = $redisManager;
    }

    public function publish(string $channel, string $body): void
    {
        try {
            $this->service->publish($channel, $body);
        } catch (Throwable $e) {
            throw new PublishException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function subscribe($channels, Closure $closure): void
    {
        $this->service->subscribe($channels, $closure);
    }
}
