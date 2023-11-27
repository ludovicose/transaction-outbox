<?php

use Ludovicose\TransactionOutbox\Brokers\RabbitMQBroker;
use Ludovicose\TransactionOutbox\Brokers\RedisBroker;
use Ludovicose\TransactionOutbox\Repositories\EloquentEventRepository;
use Ludovicose\TransactionOutbox\Serializers\JsonEventPublishSerializer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

return [
    'table_name'               => 'events',
    'delete_last_event_in_day' => 10,
    'event_repository'         => \Ludovicose\TransactionOutbox\Repositories\MemoryEventRepository::class,
    'event_publish_serialize'  => JsonEventPublishSerializer::class,

    'event_normalizers' => [
        DateTimeNormalizer::class,
        ObjectNormalizer::class,
        ArrayDenormalizer::class,
    ],

    'broker' => RedisBroker::class,

    'subscribe_channels' => [
        'serviceName.exchange.eventName',
        'serviceName.order.created'
    ],

    'enable_request_log' => false,
    'serviceName'        => env("SERVICE_NAME", 'serviceName'),

    'rabbitmq' => [
        'default_type' => 'fanout',
        'timeout'      => 25,
        'error_queue' => 'errors',
        'hosts'        => [
            [
                'host'     => env('RABBITMQ_HOST', 'rabbitmq'),
                'port'     => env('RABBITMQ_PORT', 5672),
                'user'     => env('RABBITMQ_USER', 'guest'),
                'password' => env('RABBITMQ_PASSWORD', 'guest'),
                'vhost'    => env('RABBITMQ_VHOST', '/'),
            ],
        ],

        'options' => [
            'message-ttl' => 0,
            'heartbeat'   => 60,
        ],
    ]
];
