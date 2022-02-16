<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Listeners;

use Illuminate\Http\Client\Events\ResponseReceived;
use Ludovicose\TransactionOutbox\Commands\SetSuccessEventCommand;
use Ludovicose\TransactionOutbox\Models\Event;

final class SetRequestIsSuccessListener
{
    public function handle(ResponseReceived $event)
    {
        $requestId = $event->request->data()['request_id'];

        dispatch(new SetSuccessEventCommand($requestId, Event::TYPE_REQUEST));
    }
}
