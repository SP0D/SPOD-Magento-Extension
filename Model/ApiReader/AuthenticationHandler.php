<?php

namespace Spod\Sync\Model\ApiReader;

/**
 * Contains the methods which handle
 * all requests to the /authentication resource.
 *
 * @package Spod\Sync\Model\ApiReader
 */
class AuthenticationHandler extends AbstractHandler
{
    const ACTION_BASE_URL = '/authentication';

    public function isTokenValid(string $token): bool
    {
        $result = $this->testAuthentication(self::ACTION_BASE_URL, $token);
        if ($result->getHttpCode() == 200) {
            return true;
        } else {
            return false;
        }
    }
}
