<?php

use Ludovicose\TransactionOutbox\Brokers\RedisBroker;
use Ludovicose\TransactionOutbox\Repositories\EloquentEventRepository;
use Ludovicose\TransactionOutbox\Serializers\JsonEventPublishSerializer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

return [
    'table_name'               => 'events',
    'delete_last_event_in_day' => 30,
    'event_repository'         => EloquentEventRepository::class,
    'event_publish_serialize'  => JsonEventPublishSerializer::class,

    'event_normalizers' => [
        DateTimeNormalizer::class,
        ObjectNormalizer::class,
        ArrayDenormalizer::class,
    ],

    'broker' => RedisBroker::class,

    'subscribe_channels' => [
        'test'
    ],
    'enable_request_log' => true,
];
