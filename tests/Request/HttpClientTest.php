<?php

namespace Hmaus\Spas\Tests\Request;

use GuzzleHttp\Client;
use Hmaus\Spas\Request\HttpClient;
use Hmaus\SpasParser\SpasRequest;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

class HttpClientTest extends \PHPUnit_Framework_TestCase
{
    public function testCanBuildGuzzleRequest()
    {
        $guzzle = $this->prophesize(Client::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $client = new HttpClient(
            $guzzle->reveal(),
            $logger->reveal()
        );

        $parsedRequest = new SpasRequest();
        $parsedRequest->setMethod('GET');
        $parsedRequest->setBaseUrl('http://example.com');
        $parsedRequest->setHref('/health');
        $parsedRequest->headers->set('X-Trv-Test0', 'zero');
        $parsedRequest->headers->set('X-Trv-Test1', 'one');
        $parsedRequest->setContent('I am content');

        $guzzle
            ->request(
                Argument::exact($parsedRequest->getMethod()),
                Argument::exact(
                    $parsedRequest->getBaseUrl().
                    $parsedRequest->getHref()
                ),
                Argument::exact([
                    'connect_timeout' => 10,
                    'timeout' => 10,
                    'headers' => [
                        'x-trv-test0' => ['zero'],
                        'x-trv-test1' => ['one']
                    ],
                    'body' => $parsedRequest->getContent()
                ])
            )
            ->shouldBeCalledTimes(1);

        $client->request($parsedRequest);
    }
}
