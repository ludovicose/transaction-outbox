<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Handlers;

use Ludovicose\TransactionOutbox\Commands\DeleteEventInLastDayCommand;
use Ludovicose\TransactionOutbox\Contracts\EventDeleteRepository;

final class DeleteEventInLastDayHandler
{
    public function __construct(public EventDeleteRepository $repository)
    {
    }

    public function handle(DeleteEventInLastDayCommand $command)
    {
        $this->repository->deleteLastEvent($command->day);
    }
}
