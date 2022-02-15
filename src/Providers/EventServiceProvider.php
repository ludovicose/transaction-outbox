<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Ludovicose\TransactionOutbox\Events\EventPublished;
use Ludovicose\TransactionOutbox\Events\PublishEventCreated;
use Ludovicose\TransactionOutbox\Listeners\EventPublishSuccessfullyListener;
use Ludovicose\TransactionOutbox\Listeners\PublishEventListener;

final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PublishEventCreated::class => [
            PublishEventListener::class
        ],
        EventPublished::class      => [
            EventPublishSuccessfullyListener::class
        ]
    ];
}
