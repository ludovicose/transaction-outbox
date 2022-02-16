<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Contracts;

interface EventDeleteRepository
{
    public function deleteLastEvent(int $day): void;
}
