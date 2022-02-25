<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox;

use Illuminate\Redis\RedisManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Ludovicose\TransactionOutbox\Console\EventClearCommand;
use Ludovicose\TransactionOutbox\Console\EventListenCommand;
use Ludovicose\TransactionOutbox\Console\EventRepeatCommand;
use Ludovicose\TransactionOutbox\Console\RequestRepeatCommand;
use Ludovicose\TransactionOutbox\Contracts\EventDeleteRepository;
use Ludovicose\TransactionOutbox\Contracts\EventPublishSerializer;
use Ludovicose\TransactionOutbox\Contracts\EventRepository;
use Ludovicose\TransactionOutbox\Contracts\MessageBroker;
use Ludovicose\TransactionOutbox\Contracts\RePublishEventRepository;
use Ludovicose\TransactionOutbox\Contracts\ReSendRequestRepository;
use Ludovicose\TransactionOutbox\Listeners\EventSubscriber;
use Ludovicose\TransactionOutbox\Providers\CommandBusServiceProvider;
use Ludovicose\TransactionOutbox\Providers\EventServiceProvider;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPLazyConnection;

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
        $this->app->bind(EventDeleteRepository::class, config('transaction-outbox.event_repository'));
        $this->app->bind(EventPublishSerializer::class, config('transaction-outbox.event_publish_serialize'));
        $this->app->bind(MessageBroker::class, config('transaction-outbox.broker'));

        $this->app->bind(AbstractConnection::class, function ($app) {
            $connection = config('transaction-outbox.rabbitmq.hosts');
            $options    = config('transaction-outbox.rabbitmq.options');
            return AMQPLazyConnection::create_connection($connection, $options);
        });


        $this->commands([
            EventListenCommand::class,
            EventRepeatCommand::class,
            RequestRepeatCommand::class,
            EventClearCommand::class,
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function boot()
    {
        Event::subscribe(EventSubscriber::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('transaction-outbox.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations')
            ], 'migrations');
        }
    }
}
