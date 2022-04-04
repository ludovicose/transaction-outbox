# Laravel Pub-Sub package use transactional outbox pattern

When an event is sent to the broker, the event is stored in the database, after which the event is sent to the broker.
If the event was successfully sent to the broker, we mark that the event was successfully sent.

When subscribing an event, we listen to the event from the broker. When receiving an event, we store it in the database.
After saving to the database, we send an internal event for processing.

The goal of the library is to decide the consistency of data between services, the order in which the message is sent,
idempotency.

Currently, the broker is Redis. In the future we will add other brokers message.

## Installation

You can install the package via composer:

```bash
composer require ludovicose/transaction-outbox
```

The package will automatically register itself.

You can publish the migration with:

```bash
php artisan vendor:publish --provider="Ludovicose\TransactionOutbox\PackageServiceProvider" --tag="migrations"
```

After the migration has been published you can create the table by running the migrations:

```bash
php artisan migrate
```

You can publish the config-file with:

```bash
php artisan vendor:publish --provider="Ludovicose\TransactionOutbox\PackageServiceProvider" --tag="config"
```

## Usage

### Publish Event

Create event file and implements by ShouldBePublish interface. If the ShouldBePublish interface is implemented, then the
event automatically goes to the broker Set channel name. Channel name is the name of the event that will be sent to the
broker

``` php
namespace App\Events;

use Ludovicose\TransactionOutbox\Contracts\ShouldBePublish;

class PostCreatedEvent implements ShouldBePublish 
{
    ...
    
    public function getChannel(): string
    {
        return 'channelName';
    }
    ...
}
```

To send an event to the broker, you need to run the command

```php
event(new PostCreatedEvent($someData));
```

### Subscribe

Add a channel name to the config file to understand which event to listen for. These channel names can be used as
internal events.

```php
return [
    ...
    'subscribe_channels' => [
        'channelName'
    ],
    ...
];

```

Run command in console t listen event in broker.

```bash
$ php artisan events:listen
```

Add a channel name to the EventServiceProvider file to listen for the event that came from the broker.

```php

namespace App\Providers;

... 

class EventServiceProvider extends ServiceProvider
{
    
    protected $listen = [
        ... 

        'channelName'                  => [
            SomeListener::class
        ]
        
        ...
    ];
}

```

In handle method you get the data that came from the broker

```php

namespace App\Listeners;

class SomeListener
{
    
    public function handle($event)
    {
        // $event data in event
    }
}

```

### Resubmitting events

If the event did not get into the message broker, then you can resend it with the command below, indicating the start
date and end date

```bash
$ php artisan events:repeat 2022-12-12
```

### Http request

We can also log http requests. When sending a request, we save it with ourselves and upon receiving a response, we note
that the request was successfully completed.

To register http requests, you must enable the option in the config file

```php
return [
    ...
    'enable_request_log' => true
    ...
];

```

### Resubmitting request

If the http request failed, then it can be resent with the command below, specifying the start date and end date

```bash
$ php artisan request:repeat 2022-12-12
```

### Clear event in DB

If you do not clean the database from the event, then the entries in the database can increase. To clean up old entries,
you can run the command:

```bash
$ php artisan events:clear
```

In the config file, you can specify a day after which records will be deleted

```php
return [
    ...
    'delete_last_event_in_day' => 10
    ...
];

```

## QUEUE

Add queue.php config rabbitmq

```php
return [
    ...
    'connections' => [
        ... 
         'rabbitmq' => [
            'driver'     => 'rabbitmq',
            'connection' => 'default',
            'queue'      => env('RABBITMQ_QUEUE', 'default'),
        ],
        ... 
    ]   
    ...
];

```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

