<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Console;

use Illuminate\Console\Command;
use Ludovicose\TransactionOutbox\Commands\DeleteEventInLastDayCommand;

final class EventClearCommand extends Command
{
    protected $signature = 'events:clear';

    protected $description = 'Clear events';

    public function __construct()
    {
        $this->day = config('transaction-outbox.delete_last_event_in_day', 30);
        parent::__construct();
    }

    public function handle()
    {
        dispatch(new DeleteEventInLastDayCommand($this->day));

        $this->info('Event is Clear');
    }
}
