<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Listeners;

use Illuminate\Http\Client\Events\ResponseReceived;
use Ludovicose\TransactionOutbox\Commands\SetSuccessEventCommand;

final class SetRequestIsSuccessListener
{
    public function handle(ResponseReceived $event)
    {
        $requestId = $event->request->data()['request_id'];

        dispatch(new SetSuccessEventCommand($requestId));
    }
}
