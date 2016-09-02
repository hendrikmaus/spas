<?php

namespace Hmaus\Spas\Request;

use GuzzleHttp\Client;
use Hmaus\SpasParser\ParsedRequest;
use Psr\Log\LoggerInterface;

class HttpClient
{
    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Client $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * @param ParsedRequest $request
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function request(ParsedRequest $request)
    {
        return $this->httpClient->request(
            $request->getMethod(),
            $request->getBaseUrl().$request->getHref(),
            $this->computeGuzzleOptions($request)
        );
    }

    /**
     * @param ParsedRequest $request
     * @return array
     */
    private function computeGuzzleOptions(ParsedRequest $request)
    {
        $options = [];
        $options['connect_timeout'] = 10;
        $options['timeout'] = 10;
        $options['headers'] = [];

        foreach ($request->getHeaders()->all() as $headerName => $headerValue) {
            $options['headers'][$headerName] = $headerValue;
        }

        if ($request->getContent()) {
            $options['body'] = $request->getContent();
        }

        return $options;
    }

}