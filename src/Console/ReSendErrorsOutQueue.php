<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Console;

use Illuminate\Console\Command;
use Ludovicose\TransactionOutbox\Contracts\MessageBroker;

final class ReSendErrorsOutQueue extends Command
{
    protected $signature = 'events:resend-errors';

    protected $description = 'Resend errors out queue';

    public function handle()
    {
        /** @var MessageBroker $broker */
        $broker = app(MessageBroker::class);
        $broker->reSendErrorQueue();
        ;

        $this->info('Errors is resend');
    }
}
