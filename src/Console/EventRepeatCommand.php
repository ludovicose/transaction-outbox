<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Ludovicose\TransactionOutbox\Commands\RePublishEventCommand;

final class EventRepeatCommand extends Command
{
    protected $signature = 'events:repeat
        {startDate : Date from the beginning of which you want to resend in Y-m-d format}
        {endDate? : Date before which you need to resend in the format Y-m-d}';

    protected $description = 'Forward the event to the broker';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $startDate = $this->argument('startDate');
        $endDate   = $this->argument('endDate') ?? Carbon::now()->addDay()->format('Y-m-d');

        dispatch(new RePublishEventCommand($startDate, $endDate));

        $this->info('Event is Published to Broker');
    }
}
