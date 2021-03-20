<?php

namespace Spod\Sync\Api;

interface ResultDecoder
{
    public function parsePayload($payload);
}
