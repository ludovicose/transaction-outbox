<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Listeners;

use Illuminate\Http\Client\Events\ResponseReceived;
use Ludovicose\TransactionOutbox\Commands\SetSuccessEventCommand;
use Ludovicose\TransactionOutbox\Models\Event;

final class SetRequestIsSuccessListener
{
    private bool $enableRequest;

    public function __construct()
    {
        $this->enableRequest = config('transaction-outbox.enable_request_log', true);
    }

    public function handle(ResponseReceived $event)
    {
        if (!$this->enableRequest) {
            return;
        }

        $requestId = $event->request->data()['request_id'];

        dispatch(new SetSuccessEventCommand($requestId, Event::TYPE_REQUEST));
    }
}
