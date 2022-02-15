<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Commands;

final class CreateSubscribeEventCommand
{
    public function __construct(public string $message)
    {
    }
}
