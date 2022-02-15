<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Commands;

final class CreateRequestSendingCommand
{
    public function __construct(public string $requestId, public array $data)
    {

    }
}
