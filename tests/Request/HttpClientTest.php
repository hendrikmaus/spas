<?php

namespace Hmaus\Spas\Tests\Request;

use GuzzleHttp\Client;
use Hmaus\Spas\Request\HttpClient;
use Hmaus\Spas\SpasApplication;
use Hmaus\SpasParser\SpasRequest;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

class HttpClientTest extends \PHPUnit_Framework_TestCase
{
    public function testCanBuildGuzzleRequest()
    {
        $guzzle = $this->prophesize(Client::class);
        $logger = $this->prophesize(LoggerInterface::class);
        $app = $this->prophesize(SpasApplication::class);

        $app
            ->getName()
            ->willReturn('spas')
            ->shouldBeCalledTimes(1);

        $app
            ->getVersion()
            ->willReturn('0.1.0')
            ->shouldBeCalledTimes(1);

        $client = new HttpClient(
            $guzzle->reveal(),
            $logger->reveal(),
            $app->reveal()
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
                        'x-trv-test1' => ['one'],
                        'User-Agent' => 'spas/v0.1.0', // user agent is added by the http client
                    ],
                    'body' => $parsedRequest->getContent()
                ])
            )
            ->shouldBeCalledTimes(1);

        $client->request($parsedRequest);
    }
}
