<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Ludovicose\TransactionOutbox\Console\EventListenCommand;
use Ludovicose\TransactionOutbox\Console\EventRepeatCommand;
use Ludovicose\TransactionOutbox\Console\RequestRepeatCommand;
use Ludovicose\TransactionOutbox\Contracts\EventPublishSerializer;
use Ludovicose\TransactionOutbox\Contracts\EventRepository;
use Ludovicose\TransactionOutbox\Contracts\MessageBroker;
use Ludovicose\TransactionOutbox\Contracts\RePublishEventRepository;
use Ludovicose\TransactionOutbox\Contracts\ReSendRequestRepository;
use Ludovicose\TransactionOutbox\Listeners\EventSubscriber;
use Ludovicose\TransactionOutbox\Providers\CommandBusServiceProvider;
use Ludovicose\TransactionOutbox\Providers\EventServiceProvider;

class PackageServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'transaction-outbox');

        $this->app->register(EventServiceProvider::class);
        $this->app->register(CommandBusServiceProvider::class);

        $this->app->singleton(EventSubscriber::class, fn() => new EventSubscriber());

        $this->app->bind(EventRepository::class, config('transaction-outbox.event_repository'));
        $this->app->bind(RePublishEventRepository::class, config('transaction-outbox.event_repository'));
        $this->app->bind(ReSendRequestRepository::class, config('transaction-outbox.event_repository'));
        $this->app->bind(EventPublishSerializer::class, config('transaction-outbox.event_publish_serialize'));
        $this->app->bind(MessageBroker::class, config('transaction-outbox.broker'));

        $this->commands([
            EventListenCommand::class,
            EventRepeatCommand::class,
            RequestRepeatCommand::class,
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function boot()
    {
        Event::subscribe(EventSubscriber::class);
    }
}
