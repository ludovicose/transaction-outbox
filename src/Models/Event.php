<?php

declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    public $fillable = [
        'event_id',
        'payload',
        'channel',
        'type',
    ];

    const TYPE_PUBLISH = 'publish';
    const TYPE_SUBSCRIBE = 'subscribe';
    const TYPE_REQUEST = 'request';
    const DEFAULT_CHANNEL = 'default';

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function getId(): string
    {
        return $this->event_id;
    }

    public function setSuccessEvent()
    {
        $this->success_at = now();
    }
}
