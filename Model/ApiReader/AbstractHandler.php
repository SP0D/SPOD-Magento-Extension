<?php

namespace Spod\Sync\Model\ApiReader;

use Spod\Sync\Api\ResultDecoder;
use Spod\Sync\Helper\ConfigHelper;

abstract class AbstractHandler
{
    /** @var ConfigHelper */
    protected $configHelper;
    /** @var ResultDecoder */
    protected $decoder;

    public function __construct(
        ConfigHelper $configHelper,
        ResultDecoder $decoder
    )
    {
        $this->configHelper = $configHelper;
        $this->decoder = $decoder;
    }

    protected function fetchResult($apiAction)
    {

        $baseUrl = $this->configHelper->getApiUrl();
        $url = sprintf("%s%s", $baseUrl, $apiAction);

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_HTTPHEADER, $this->getTokenHeader());
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

        return curl_exec($ch);
    }

    protected function getTokenHeader(): array
    {
        return [
            sprintf('X-SPOD-ACCESS-TOKEN: %s', $this->configHelper->getToken())
        ];
    }

    protected function getParsedApiResult($apiAction)
    {
        $result = $this->fetchResult($apiAction);
        return $this->decoder->parsePayload($result);
    }
}
