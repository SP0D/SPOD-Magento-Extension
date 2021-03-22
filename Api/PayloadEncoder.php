<?php

namespace Spod\Sync\Api;

interface PayloadEncoder
{
    public function encodePayload($payload);
}
