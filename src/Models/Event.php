<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Models;

final class Event
{
    public function __construct()
    {

    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function getId()
    {
        return $this->id;
    }
}
