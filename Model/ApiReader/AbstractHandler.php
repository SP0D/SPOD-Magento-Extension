<?php

namespace Spod\Sync\Model\ApiReader;

use Spod\Sync\Api\PayloadEncoder;
use Spod\Sync\Api\ResultDecoder;
use Spod\Sync\Helper\ConfigHelper;
use Spod\Sync\Model\ApiResult;
use Spod\Sync\Model\ApiResultFactory;

abstract class AbstractHandler
{
    /** @var ApiResultFactory  */
    protected $apiResultFactory;
    /** @var ConfigHelper */
    protected $configHelper;
    /** @var ResultDecoder */
    protected $decoder;
    /** @var PayloadEncoder  */
    protected $encoder;

    public function __construct(
        ApiResultFactory $apiResultFactory,
        ConfigHelper $configHelper,
        PayloadEncoder $encoder,
        ResultDecoder $decoder
    ) {
        $this->apiResultFactory = $apiResultFactory;
        $this->configHelper = $configHelper;
        $this->decoder = $decoder;
        $this->encoder = $encoder;
    }

    /**
     * POST request to API
     *
     * @param string $apiAction
     * @param array $params
     * @return bool|string
     */
    protected function sendPostRequest(string $apiAction, array $params): ApiResult
    {
        $baseUrl = $this->configHelper->getApiUrl();
        $url = sprintf("%s%s", $baseUrl, $apiAction);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->encoder->encodePayload($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = $this->apiResultFactory->create();
        $result->setPayload(curl_exec($ch));
        $result->setHttpCode(curl_getinfo($ch, CURLINFO_HTTP_CODE));

        return $result;
    }

    /**
     * DELETE API resource
     *
     * @param string $apiAction
     * @return bool|string
     */
    protected function sendDeleteRequest(string $apiAction): ApiResult
    {
        $baseUrl = $this->configHelper->getApiUrl();
        $url = sprintf("%s%s", $baseUrl, $apiAction);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);

        $result = $this->apiResultFactory->create();
        $result->setHttpCode(curl_getinfo($ch, CURLINFO_HTTP_CODE));

        return $result;
    }

    /**
     * GET response from server
     *
     * @param $apiAction
     * @return bool|string
     */
    protected function fetchResult($apiAction): ApiResult
    {
        $baseUrl = $this->configHelper->getApiUrl();
        $url = sprintf("%s%s", $baseUrl, $apiAction);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = $this->apiResultFactory->create();
        $result->setPayload(curl_exec($ch));
        $result->setHttpCode(curl_getinfo($ch, CURLINFO_HTTP_CODE));

        return $result;
    }

    protected function getHeader(): array
    {
        return [
            sprintf('Content-Type: application/json'),
            sprintf('X-SPOD-ACCESS-TOKEN: %s', $this->configHelper->getToken())
        ];
    }

    /**
     * Convenience method called to POST
     * a request and get parsed result back.
     *
     * @param string $apiAction
     * @param array $params
     * @return mixed
     */
    protected function postRequest(string $apiAction, array $params): ApiResult
    {
        $result = $this->sendPostRequest($apiAction, $params);
        $result->setPayload($this->decoder->parsePayload($result->getPayload()));

        return $result;
    }

    /**
     * Convenience method called to GET
     * a response from a certain resource.
     *
     * @param $apiAction
     * @return mixed
     */
    protected function getParsedApiResult($apiAction): ApiResult
    {
        $result = $this->fetchResult($apiAction);
        $result->setPayload($this->decoder->parsePayload($result->getPayload()));

        return $result;
    }
}
