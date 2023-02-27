<?php

declare(strict_types=1);

namespace Spod\Sync\Model;

interface WebhookEventsReader
{
    public function read(): iterable;
}
