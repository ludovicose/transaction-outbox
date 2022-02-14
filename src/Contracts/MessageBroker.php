<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Contracts;

use Closure;

interface MessageBroker
{
    public function publish(string $channel, string $body): void;

    public function subscribe($channels, Closure $closure): void;
}
