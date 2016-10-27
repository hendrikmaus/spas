<?php

namespace Hmaus\Spas\Tests\Request;

use GuzzleHttp\Client;
use Hmaus\Spas\Request\HttpClient;
use Hmaus\Spas\SpasApplication;
use Hmaus\Spas\Parser\SpasRequest;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class HttpClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client|ObjectProphecy
     */
    private $guzzle;

    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $logger;

    /**
     * @var SpasApplication|ObjectProphecy
     */
    private $spasApp;

    /**
     * @var HttpClient
     */
    private $client;

    protected function setUp()
    {
        $this->guzzle = $this->prophesize(Client::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->spasApp = $this->prophesize(SpasApplication::class);

        $this->client = new HttpClient(
            $this->guzzle->reveal(),
            $this->logger->reveal(),
            $this->spasApp->reveal()
        );
    }

    public function testCanBuildGuzzleRequest()
    {
        $parsedRequest = new SpasRequest();
        $parsedRequest->setMethod('GET');
        $parsedRequest->setBaseUrl('http://example.com');
        $parsedRequest->setHref('/health');

        $this->guzzle
            ->request(
                Argument::exact($parsedRequest->getMethod()),
                Argument::exact(
                    $parsedRequest->getBaseUrl().
                    $parsedRequest->getHref()
                ),
                Argument::type('array')
            )
            ->shouldBeCalledTimes(1);

        $this->client->request($parsedRequest);
    }

    public function testCanComputeGuzzleOptions()
    {
        $this->spasApp
            ->getName()
            ->willReturn('spas')
            ->shouldBeCalledTimes(1);

        $this->spasApp
            ->getVersion()
            ->willReturn('0.1.0')
            ->shouldBeCalledTimes(1);

        $parsedRequest = new SpasRequest();
        $parsedRequest->setMethod('GET');
        $parsedRequest->setBaseUrl('http://example.com');
        $parsedRequest->setHref('/health');
        $parsedRequest->headers->set('X-Vnd-Test0', 'zero');
        $parsedRequest->headers->set('X-Vnd-Test1', 'one');
        $parsedRequest->setContent('I am content');

        $options = $this->client->computeGuzzleOptions($parsedRequest);

        $this->assertArrayHasKey('headers', $options);

        $expected = [
            'x-vnd-test0' => ['zero'],
            'x-vnd-test1' => ['one'],
            'User-Agent'  => 'spas/v0.1.0'
        ];

        $this->assertSame($expected, $options['headers']);
    }
}
