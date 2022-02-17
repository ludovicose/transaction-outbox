<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Listeners;

use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Support\Str;
use Ludovicose\TransactionOutbox\Commands\CreateRequestSendingCommand;

final class SaveRequestSendingListener
{
    private bool $enableRequest;

    public function __construct()
    {
        $this->enableRequest = config('transaction-outbox.enable_request_log', true);
    }

    public function handle(RequestSending $event)
    {
        if (!$this->enableRequest) {
            return;
        }

        $data = [
            'method'  => $event->request->method(),
            'headers' => $event->request->headers(),
            'data'    => $event->request->data(),
            'url'     => $event->request->url(),
        ];

        $requestId = $event->request->data()['request_id'] ?? Str::uuid()->toString();

        $event->request->withData(['request_id' => $requestId]);

        dispatch(new CreateRequestSendingCommand($requestId, $data));
    }
}
