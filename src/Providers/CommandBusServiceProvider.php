<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Providers;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\ServiceProvider;
use Ludovicose\TransactionOutbox\Commands\CreatePublishEventCommand;
use Ludovicose\TransactionOutbox\Commands\CreateRequestSendingCommand;
use Ludovicose\TransactionOutbox\Commands\CreateSubscribeEventCommand;
use Ludovicose\TransactionOutbox\Commands\DeleteEventInLastDayCommand;
use Ludovicose\TransactionOutbox\Commands\PublishEventToBrokerCommand;
use Ludovicose\TransactionOutbox\Commands\RePublishEventCommand;
use Ludovicose\TransactionOutbox\Commands\ReSendRequestCommand;
use Ludovicose\TransactionOutbox\Commands\SetSuccessEventCommand;
use Ludovicose\TransactionOutbox\Handlers\CreatePublishEventHandler;
use Ludovicose\TransactionOutbox\Handlers\CreateRequestSendingHandler;
use Ludovicose\TransactionOutbox\Handlers\CreateSubscribeEventHandler;
use Ludovicose\TransactionOutbox\Handlers\DeleteEventInLastDayHandler;
use Ludovicose\TransactionOutbox\Handlers\PublishEventToBrokerHandler;
use Ludovicose\TransactionOutbox\Handlers\RePublishEventHandler;
use Ludovicose\TransactionOutbox\Handlers\ReSendRequestHandler;
use Ludovicose\TransactionOutbox\Handlers\SetSuccessEventHandler;

class CommandBusServiceProvider extends ServiceProvider
{
    private array $maps = [
        CreatePublishEventCommand::class   => CreatePublishEventHandler::class,
        PublishEventToBrokerCommand::class => PublishEventToBrokerHandler::class,
        SetSuccessEventCommand::class      => SetSuccessEventHandler::class,
        CreateSubscribeEventCommand::class => CreateSubscribeEventHandler::class,
        CreateRequestSendingCommand::class => CreateRequestSendingHandler::class,
        RePublishEventCommand::class       => RePublishEventHandler::class,
        ReSendRequestCommand::class        => ReSendRequestHandler::class,
        DeleteEventInLastDayCommand::class => DeleteEventInLastDayHandler::class,
    ];

    public function boot()
    {
        Bus::map($this->maps);
    }
}
