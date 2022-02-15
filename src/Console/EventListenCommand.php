<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Console;

use Illuminate\Console\Command;
use Ludovicose\TransactionOutbox\Commands\CreateSubscribeEventCommand;
use Ludovicose\TransactionOutbox\Contracts\MessageBroker;

final class EventListenCommand extends Command
{
    protected $signature = 'event:listen';

    protected $description = 'Listen to events with this command';

    private MessageBroker $broker;

    protected array $channels = [
        'test'
    ];

    public function __construct(MessageBroker $broker)
    {
        $this->broker = $broker;
        parent::__construct();
    }

    public function handle()
    {
        $this->broker->subscribe($this->channels, function ($message) {
            dispatch(new CreateSubscribeEventCommand($message));
        });
    }
}
