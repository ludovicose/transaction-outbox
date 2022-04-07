<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Queue;

use Illuminate\Support\Str;

final class QueueName
{
    public static function getName(?string $name): ?string
    {
        $serviceName = config('transaction-outbox.serviceName', '');

        if (Str::contains($name, $serviceName)) {
            return $name;
        }

        return $serviceName ? "{$serviceName}.queue.{$name}" : $name;
    }
}
