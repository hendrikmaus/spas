<?php

namespace Hmaus\Spas\Request;

use GuzzleHttp\Client;
use Hmaus\Spas\SpasApplication;
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

    /**
     * @var SpasApplication
     */
    private $application;

    public function __construct(Client $httpClient, LoggerInterface $logger, SpasApplication $application)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->application = $application;
    }

    /**
     * @param ParsedRequest $request
     * @return \Psr\Http\Message\ResponseInterface
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
    private function computeGuzzleOptions(ParsedRequest $request) : array
    {
        $options = [];
        $options['connect_timeout'] = 10;
        $options['timeout'] = 10;
        $options['headers'] = [];

        foreach ($request->getHeaders()->all() as $headerName => $headerValue) {
            $options['headers'][$headerName] = $headerValue;
        }

        $options['headers']['User-Agent'] = sprintf(
            '%s/v%s',
            $this->application->getName(),
            $this->application->getVersion()
        );

        if ($request->getContent()) {
            $options['body'] = $request->getContent();
        }

        return $options;
    }

}
