<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Contracts;

interface ShouldBePublish
{
    public function getChannel(): string;
}
