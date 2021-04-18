<?php

namespace Spod\Sync\Model\Mapping;

/**
 * Class which maps the numeric queue status
 * to a human readable constant which can be used
 * in the code.
 *
 * @package Spod\Sync\Model\Mapping
 */
class QueueStatus
{
    const STATUS_PENDING = 1;
    const STATUS_ERROR = 2;
    const STATUS_PROCESSED = 3;
}
