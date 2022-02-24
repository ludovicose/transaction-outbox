<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Commands;

final class DeleteEventInLastDayCommand
{
    public function __construct(public int $day)
    {
    }
}
