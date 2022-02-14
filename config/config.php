<?php

use Ludovicose\TransactionOutbox\Repositories\MemoryEventRepository;
use Ludovicose\TransactionOutbox\Serializers\JsonEventPublishSerializer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

return [
    'table_name'              => 'events',
    'event_repository'        => MemoryEventRepository::class,
    'event_publish_serialize' => JsonEventPublishSerializer::class,

    'event_normalizers' => [
        DateTimeNormalizer::class,
        ObjectNormalizer::class,
        ArrayDenormalizer::class,
    ],

    'broker' => \Ludovicose\TransactionOutbox\Brokers\RedisBroker::class,
];
