<?php

namespace Spod\Sync\Model\ApiReader;

use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\ConfigHelper;
use Spod\Sync\Model\ApiResultFactory;

/**
 * Contains the methods which handle
 * all requests to the /authentication resource.
 *
 * @package Spod\Sync\Model\ApiReader
 */
class AuthenticationHandler extends AbstractHandler
{
    const ACTION_BASE_URL = '/authentication';
    /**
     * @var SpodLoggerInterface
     */
    private $spodLogger;

    public function __construct(
        ApiResultFactory $apiResultFactory,
        ConfigHelper $configHelper,
        SpodLoggerInterface $spodLogger
    ) {
        $this->spodLogger = $spodLogger;
        parent::__construct($apiResultFactory, $configHelper);
    }

    public function isTokenValid(string $token): bool
    {
        $result = $this->testAuthentication(self::ACTION_BASE_URL, $token);
        $this->spodLogger->logDebug(sprintf("Return code: %d", $result->getHttpCode()), 'Token Validation');
        if ($result->getHttpCode() == 200) {
            $this->spodLogger->logDebug("token is valid", 'Token Validation');
            return true;
        } else {
            return false;
        }
    }
}
