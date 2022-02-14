<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Providers;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\ServiceProvider;
use Ludovicose\TransactionOutbox\Commands\CreatePublishEventCommand;
use Ludovicose\TransactionOutbox\Commands\PublishEventToBrokerCommand;
use Ludovicose\TransactionOutbox\Handlers\CreatePublishEventHandler;
use Ludovicose\TransactionOutbox\Handlers\PublishEventToBrokerHandler;

class CommandBusServiceProvider extends ServiceProvider
{
    private array $maps = [
        CreatePublishEventCommand::class   => CreatePublishEventHandler::class,
        PublishEventToBrokerCommand::class => PublishEventToBrokerHandler::class,
    ];

    public function boot()
    {
        Bus::map($this->maps);
    }
}
