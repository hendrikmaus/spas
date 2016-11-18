<?php

namespace Hmaus\Spas\Request;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\SpasApplication;
use Psr\Log\LoggerInterface;

class HttpClient
{
    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var LoggerInterface
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
            $request->getBaseUrl() . $request->getHref(),
            $this->computeGuzzleOptions($request)
        );
    }

    /**
     * Put Guzzle options array together.
     * @see http://guzzle.readthedocs.io/en/latest/request-options.html
     *
     * allow_redirects
     *   do not allow to follow redirects as we want to test the actual 3xx codes as well
     *   todo maybe we need an option to configure this behvaiour from the outside
     *
     * connect_timeout
     *   spas should not wait the default (indefinitely) to connect
     *
     * timeout
     *   spas should not wait the default (indefinitely) for a request
     *
     * http_errors
     *   guzzle must not throw exceptions for http protocol errors as we validate them on our own
     *
     * headers
     *   add all headers contained in the given request.
     *   add spas user agent string
     *
     * synchronous
     *   tell handlers and middleware that we intend to wait on the response
     *
     * decode_content
     *   guzzle shall decode gzip etc automatically
     *
     * veriffy
     *   do not verify ssl certs
     *
     * body
     *   add body contained in the given request
     *
     * on_stats
     *   get access to statistics about the request like total time
     *
     * @param ParsedRequest $request
     * @return array
     */
    public function computeGuzzleOptions(ParsedRequest $request) : array
    {
        $options = [];
        $options['allow_redirects'] = false;
        $options['connect_timeout'] = 10;
        $options['timeout'] = 10;
        $options['http_errors'] = false;
        $options['headers'] = [];
        $options['synchronous'] = true;
        $options['decode_content'] = true;
        $options['verify'] = false;

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

        // todo if we want to log stats, this should be refactored
        $options['on_stats'] = function (TransferStats $stats) {
            $stats = $stats->getHandlerStats();
            $size = (int)$stats['size_download'];
            $time = round($stats['total_time'], 3);

            $this->logger->info(
                sprintf('Received %d bytes in %g seconds', $size, $time)
            );
        };

        return $options;
    }

}
