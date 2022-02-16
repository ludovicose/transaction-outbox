<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Commands;

final class SetSuccessEventCommand
{
    public function __construct(public string $id, public string $type)
    {
    }
}
