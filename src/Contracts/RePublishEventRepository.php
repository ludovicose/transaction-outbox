<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Contracts;

use Illuminate\Support\Collection;

interface RePublishEventRepository
{
    public function getNotPublishEventBy(string $startDate, string $endDate): Collection;
}
