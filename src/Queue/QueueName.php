<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Queue;

final class QueueName
{
    public static function getName(?string $name): ?string
    {
        $serviceName = config('transaction-outbox.serviceName', '');
        return $serviceName ? "{$serviceName}.queue.{$name}" : $name;
    }
}
