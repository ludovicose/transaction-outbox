<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
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
