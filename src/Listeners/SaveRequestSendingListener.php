<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Listeners;

use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Support\Str;
use Ludovicose\TransactionOutbox\Commands\CreateRequestSendingCommand;

final class SaveRequestSendingListener
{
    public function handle(RequestSending $event)
    {
        $data = [
            'method'  => $event->request->method(),
            'headers' => $event->request->headers(),
            'data'    => $event->request->data(),
            'url'     => $event->request->url(),
        ];

        $requestId = Str::uuid()->toString();
        $event->request->withData(['request_id' => $requestId]);

        dispatch(new CreateRequestSendingCommand($requestId, $data));
    }
}
