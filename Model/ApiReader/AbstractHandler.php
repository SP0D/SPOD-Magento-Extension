<?php

declare(strict_types=1);

namespace Spod\Sync\Model\ApiReader;

use GuzzleHttp\ClientInterface;
use Spod\Sync\Api\PayloadEncoder;
use Spod\Sync\Api\ResultDecoder;
use Spod\Sync\Helper\ConfigHelper;
use Spod\Sync\Model\ApiResult;
use Spod\Sync\Model\ApiResultFactory;

/**
 * Abstract base class for API handlers. Extended
 * by each specific handler which in turn
 * communicates with the SPOD API.
 *
 * @package Spod\Sync\Model\ApiReader
 */
abstract class AbstractHandler
{
    /** @var ClientInterface */
    private $httpClient;

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
        $this->httpClient = new \GuzzleHttp\Client([
            'base_uri' => $configHelper->getApiUrl(),
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Magento/1.0'
            ]
        ]);
        $this->apiResultFactory = $apiResultFactory;
        $this->configHelper = $configHelper;
        $this->decoder = $decoder;
        $this->encoder = $encoder;
    }

    protected function testAuthentication(string $apiAction, string $apiToken): ApiResult
    {
        try {
            $response = $this->httpClient->send(
                new \GuzzleHttp\Psr7\Request('GET', $apiAction, ['X-SPOD-ACCESS-TOKEN' => $apiToken])
            );
            $result = $this->apiResultFactory->createFromResponse($response);
        } catch (\GuzzleHttp\Exception\GuzzleException $ge) {
            $result = $this->apiResultFactory->create();
        }

        return $result;
    }

    /**
     * POST request to API
     *
     * @param string $apiAction
     * @param array $params
     * @return bool|string
     * @throws \Exception if SPOD API Key is missing
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function sendPostRequest(string $apiAction, array $params): ApiResult
    {
        $response = $this->httpClient->send(
            new \GuzzleHttp\Psr7\Request(
                'POST',
                $apiAction,
                ['X-SPOD-ACCESS-TOKEN' => $this->fetchSpodApiKey()],
                $this->encoder->encodePayload($params)
            )
        );

        return $this->apiResultFactory->createFromResponse($response);
    }

    protected function sendPutRequest(string $apiAction, array $params): ApiResult
    {
        $response = $this->httpClient->send(
            new \GuzzleHttp\Psr7\Request(
                'PUT',
                $apiAction,
                ['X-SPOD-ACCESS-TOKEN' => $this->fetchSpodApiKey()],
                $this->encoder->encodePayload($params)
            )
        );

        return $this->apiResultFactory->createFromResponse($response);
    }

    /**
     * DELETE API resource
     *
     * @param string $apiAction
     * @return ApiResult
     * @throws \Exception if SPOD API Key is missing
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function sendDeleteRequest(string $apiAction): ApiResult
    {
        $response = $this->httpClient->send(
            new \GuzzleHttp\Psr7\Request('DELETE', $apiAction, ['X-SPOD-ACCESS-TOKEN' => $this->fetchSpodApiKey()])
        );

        return $this->apiResultFactory->createFromResponse($response);
    }

    /**
     * GET response from server
     *
     * @param string $apiAction
     * @return ApiResult
     * @throws \Exception if SPOD API Key is missing
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function fetchResult(string $apiAction): ApiResult
    {
        $response = $this->httpClient->send(
            new \GuzzleHttp\Psr7\Request('GET', $apiAction, ['X-SPOD-ACCESS-TOKEN' => $this->fetchSpodApiKey()])
        );

        return $this->apiResultFactory->createFromResponse($response);
    }

    /**
     * @return array
     */
    protected function getDefaultHeader(): array
    {
        $token = $this->configHelper->getToken();
        if (!$token) {
            throw new \Exception("API connection has not been established. Token empty.");
        }
        return $this->builtAuthHeader($token);
    }

    /**
     * @param string $token
     * @return array
     */
    protected function builtAuthHeader(string $token): array
    {
        return [
            sprintf('Content-Type: application/json'),
            sprintf('X-SPOD-ACCESS-TOKEN: %s', $token)
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
    protected function postRequest(string $apiAction, array $params = []): ApiResult
    {
        return $this->sendPostRequest($apiAction, $params);
    }

    /**
     * Convenience method called to PUT
     * a request and get parsed result back.
     *
     * @param string $apiAction
     * @param array $params
     * @return mixed
     */
    protected function putRequest(string $apiAction, array $params = []): ApiResult
    {
        $result = $this->sendPutRequest($apiAction, $params);
        $result->setPayload($this->decoder->parsePayload($result->getPayload()));

        return $result;
    }

    /**
     * @return string
     * @throws \Exception if SPOD connection has not been established earlier
     */
    private function fetchSpodApiKey(): string
    {
        $apiToken = $this->configHelper->getToken();
        if (!$apiToken) {
            throw new \Exception("API connection has not been established. SPOD API Key is missing.");
        }
        return $apiToken;
    }
}
