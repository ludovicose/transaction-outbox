<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Ludovicose\TransactionOutbox\Events\EventPublished;
use Ludovicose\TransactionOutbox\Events\PublishEventCreated;
use Ludovicose\TransactionOutbox\Listeners\EventPublishSuccessfullyListener;
use Ludovicose\TransactionOutbox\Listeners\PublishEventListener;
use Ludovicose\TransactionOutbox\Listeners\RequestSendingListener;
use Ludovicose\TransactionOutbox\Listeners\SaveRequestSendingListener;
use Ludovicose\TransactionOutbox\Listeners\SetRequestIsSuccessListener;

final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PublishEventCreated::class => [
            PublishEventListener::class
        ],
        EventPublished::class      => [
            EventPublishSuccessfullyListener::class
        ],
        RequestSending::class      => [
            SaveRequestSendingListener::class
        ],
        ResponseReceived::class    => [
            SetRequestIsSuccessListener::class
        ],
    ];
}
