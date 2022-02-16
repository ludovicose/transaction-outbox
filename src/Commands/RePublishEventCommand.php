<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Commands;

final class RePublishEventCommand
{
    public function __construct(public string $startDate, public string $endDate)
    {
    }
}
