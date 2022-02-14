<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Contracts;

interface EventPublishSerializer
{
    public function serialize(ShouldBePublish $event): string;

    public function deserialize(
        string $eventClass,
        string $json,
    ): ShouldBePublish;
}
