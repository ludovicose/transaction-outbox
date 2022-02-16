<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Contracts;

use Illuminate\Support\Collection;

interface ReSendRequestRepository
{
    public function getNotSendRequestBy(string $startDate, string $endDate): Collection;
}
