<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Serializers;

use Ludovicose\TransactionOutbox\Contracts\EventPublishSerializer;
use Ludovicose\TransactionOutbox\Contracts\ShouldBePublish;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;

final class JsonEventPublishSerializer implements EventPublishSerializer
{
    private SymfonySerializer $serializer;

    public function __construct()
    {
        $encoders    = [new JsonEncoder()];
        $normalizers = array_map(
            fn($className) => new $className(),
            config('transaction-outbox.event_normalizers')
        );

        $this->serializer = new SymfonySerializer($normalizers, $encoders);
    }

    public function serialize(ShouldBePublish $event): string
    {
        if (method_exists($event, '__sleep')) {
            $event->__sleep();
        }

        return $this->serializer->serialize($event, 'json');
    }

    public function deserialize(string $eventClass, string $json,): ShouldBePublish
    {
        return $this->serializer->deserialize($json, $eventClass, 'json');
    }
}
