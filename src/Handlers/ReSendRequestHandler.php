<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Handlers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Ludovicose\TransactionOutbox\Commands\PublishEventToBrokerCommand;
use Ludovicose\TransactionOutbox\Commands\ReSendRequestCommand;
use Ludovicose\TransactionOutbox\Contracts\ReSendRequestRepository;

final class ReSendRequestHandler
{
    public function __construct(public ReSendRequestRepository $repository)
    {
    }

    public function handle(ReSendRequestCommand $command)
    {
        $events = $this->repository->getNotSendRequestBy($command->startDate, $command->endDate);

        $events->each(function ($item) {
            $payload = json_decode($item->payload, true);
            $method  = Str::lower($payload['method']);
            $data    = [...$payload['data'], 'request_id' => $item->event_id];

            $httpClient = Http::withHeaders($payload['headers']);
            $httpClient->$method($payload['url'], $data);
        });
    }
}
