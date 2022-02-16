<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Handlers;

use Ludovicose\TransactionOutbox\Commands\PublishEventToBrokerCommand;
use Ludovicose\TransactionOutbox\Commands\RePublishEventCommand;
use Ludovicose\TransactionOutbox\Contracts\RePublishEventRepository;

final class RePublishEventHandler
{
    public function __construct(public RePublishEventRepository $repository)
    {
    }

    public function handle(RePublishEventCommand $command)
    {
        $events = $this->repository->getNotPublishEventBy($command->startDate, $command->endDate);

        $events->each(function ($item) {
            dispatch(new PublishEventToBrokerCommand($item));
        });
    }
}
