<?php
declare(strict_types=1);

namespace Ludovicose\TransactionOutbox\Commands;

use Ludovicose\TransactionOutbox\Contracts\ShouldBePublish;

final class CreatePublishEventCommand
{
    public function __construct(public ShouldBePublish $event)
    {

    }
}
