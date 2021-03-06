<?php

namespace Hmaus\Spas\Tests\Request\Result;

use GuzzleHttp\Exception\RequestException;
use Hmaus\Spas\Formatter\FormatterService;
use Hmaus\Spas\Formatter\JsonFormatter;
use Hmaus\Spas\Request\Result\ExceptionHandler;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class ExceptionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $logger;

    /**
     * @var ExceptionHandler
     */
    private $handler;

    /**
     * @var FormatterService|ObjectProphecy
     */
    private $formatterService;

    /**
     * @var JsonFormatter|ObjectProphecy
     */
    private $jsonFormatter;

    protected function setUp()
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->formatterService = $this->prophesize(FormatterService::class);

        $this->handler = new ExceptionHandler(
            $this->logger->reveal(),
            $this->formatterService->reveal()
        );

        $this->jsonFormatter = $this->prophesize(JsonFormatter::class);
        $this
            ->jsonFormatter
            ->getContentTypes()
            ->willReturn(['application/json']);
    }

    public function testLoggerWillWriteGenericExceptions()
    {
        $message = 'Generic testing error';

        $this
            ->logger
            ->error(Argument::exact($message))
            ->shouldBeCalledTimes(1);

        $genericException = new \Exception($message);

        $this
            ->handler
            ->handle($genericException);
    }

    public function testLoggerWillHandleResponselessExceptions()
    {
        $message = 'I do not have a response on me';

        $this
            ->logger
            ->error(Argument::exact($message))
            ->shouldBeCalledTimes(1);

        $request = $this->prophesize(RequestInterface::class);
        $responseLessException = new RequestException($message, $request->reveal());

        $this
            ->handler
            ->handle($responseLessException);
    }

    public function testHandlerWillLogStatusCodeAndReasonPhrase()
    {
        $message = 'I have a response on me';
        
        $this
            ->logger
            ->error(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledTimes(1);

        $request = $this->prophesize(RequestInterface::class);

        $responseBody = $this->prophesize(StreamInterface::class);
        $responseBody
            ->getContents()
            ->willReturn(null);

        $response = $this->prophesize(ResponseInterface::class);
        $response
            ->getStatusCode()
            ->willReturn(500);
        $response
            ->getReasonPhrase()
            ->willReturn('I am the reason');
        $response
            ->getBody()
            ->willReturn($responseBody->reveal());

        $responseLessException = new RequestException($message, $request->reveal(), $response->reveal());

        $this
            ->handler
            ->handle($responseLessException);
    }

    public function testHandlerWillTryToPrintByContentType()
    {
        $message = 'I have a response on me';
        $body = 'I am the body';

        $this
            ->logger
            ->error(Argument::cetera())
            ->shouldBeCalledTimes(2);

        $request = $this->prophesize(RequestInterface::class);

        $responseBody = $this->prophesize(StreamInterface::class);
        $responseBody
            ->getContents()
            ->willReturn($body);

        $response = $this->prophesize(ResponseInterface::class);
        $response
            ->getStatusCode()
            ->willReturn(500);
        $response
            ->getReasonPhrase()
            ->willReturn('I am the reason');
        $response
            ->getBody()
            ->willReturn($responseBody->reveal());
        $response
            ->getHeaderLine(Argument::exact('content-type'))
            ->willReturn('application/json');

        $this
            ->formatterService
            ->getFormatterByContentType(Argument::exact('application/json'))
            ->willReturn($this->jsonFormatter->reveal());

        $this
            ->jsonFormatter
            ->format(Argument::exact($body))
            ->shouldBeCalledTimes(1);

        $responseLessException = new RequestException($message, $request->reveal(), $response->reveal());

        $this
            ->handler
            ->handle($responseLessException);
    }

}
