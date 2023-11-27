<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox;

use Illuminate\Queue\QueueManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Ludovicose\TransactionOutbox\Console\EventClearCommand;
use Ludovicose\TransactionOutbox\Console\EventListenCommand;
use Ludovicose\TransactionOutbox\Console\EventRepeatCommand;
use Ludovicose\TransactionOutbox\Console\RequestRepeatCommand;
use Ludovicose\TransactionOutbox\Console\ReSendErrorsOutQueue;
use Ludovicose\TransactionOutbox\Contracts\EventDeleteRepository;
use Ludovicose\TransactionOutbox\Contracts\EventPublishSerializer;
use Ludovicose\TransactionOutbox\Contracts\EventRepository;
use Ludovicose\TransactionOutbox\Contracts\MessageBroker;
use Ludovicose\TransactionOutbox\Contracts\RePublishEventRepository;
use Ludovicose\TransactionOutbox\Contracts\ReSendRequestRepository;
use Ludovicose\TransactionOutbox\Listeners\EventSubscriber;
use Ludovicose\TransactionOutbox\Providers\CommandBusServiceProvider;
use Ludovicose\TransactionOutbox\Providers\EventServiceProvider;
use Ludovicose\TransactionOutbox\Queue\Connectors\RabbitMQConnector;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use PhpAmqpLib\Connection\AMQPConnectionFactory;

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

            if($app->runningUnitTests()){
                return;
            }

            $connectionConfig = config('transaction-outbox.rabbitmq.hosts');
            $options    = config('transaction-outbox.rabbitmq.options');

            $connectionConfig = Arr::first($connectionConfig);
            $config     = new AMQPConnectionConfig();
            $config->setHost($connectionConfig['host']);
            $config->setPort((int)$connectionConfig['port']);
            $config->setUser($connectionConfig['user']);
            $config->setPassword($connectionConfig['password']);
            $config->setVhost($connectionConfig['vhost']);
            $config->setHeartbeat($options['heartbeat']);

            return AMQPConnectionFactory::create($config);
        });


        $this->commands([
            EventListenCommand::class,
            EventRepeatCommand::class,
            RequestRepeatCommand::class,
            EventClearCommand::class,
            ReSendErrorsOutQueue::class
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function boot()
    {
        Event::subscribe(EventSubscriber::class);

        /**@var QueueManager $queue */
        $queue = $this->app['queue'];

        $queue->addConnector('rabbitmq', function () {
            return new RabbitMQConnector($this->app['events']);
        });

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
