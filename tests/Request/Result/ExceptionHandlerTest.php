<?php

namespace Hmaus\Spas\Tests\Request\Result;

use GuzzleHttp\Exception\RequestException;
use Hmaus\Spas\Request\Result\ExceptionHandler;
use Hmaus\Spas\Request\Result\Printer\JsonPrinter;
use Hmaus\Spas\Request\Result\Printer\Printer;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

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
     * @var Printer|ObjectProphecy
     */
    private $universalPrinter;

    /**
     * @var Printer|ObjectProphecy
     */
    private $jsonPrinter;

    protected function setUp()
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->universalPrinter = $this->prophesize(Printer::class);

        $this->handler = new ExceptionHandler(
            $this->logger->reveal(),
            $this->universalPrinter->reveal()
        );

        $this->jsonPrinter = $this->prophesize(JsonPrinter::class);
        $this
            ->jsonPrinter
            ->getContentType()
            ->willReturn('application/json');
        
        $this
            ->handler
            ->addPrinter($this->jsonPrinter->reveal());
    }

    public function testUniversalPrinterWillHandleGenericExceptions()
    {
        $this
            ->universalPrinter
            ->printIt(Argument::type('string'), Argument::exact('error'))
            ->shouldBeCalledTimes(1);

        $genericException = new \Exception('Generic Testing Error');

        $this
            ->handler
            ->handle($genericException);
    }

    public function testUniversalPrinterWillHandleResponselessExceptions()
    {
        $message = 'I do not have a response on me';

        $this
            ->universalPrinter
            ->printIt(Argument::exact($message), Argument::exact('error'))
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
            ->universalPrinter
            ->printIt(Argument::cetera())
            ->shouldNotBeCalled();
        
        $this
            ->logger
            ->error(Argument::type('string'))
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
            ->universalPrinter
            ->printIt(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->logger
            ->error(Argument::type('string'))
            ->shouldBeCalledTimes(1);

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
            ->jsonPrinter
            ->printIt(Argument::exact($body), Argument::exact(LogLevel::ERROR))
            ->shouldBeCalledTimes(1);

        $responseLessException = new RequestException($message, $request->reveal(), $response->reveal());

        $this
            ->handler
            ->handle($responseLessException);
    }

    public function testUniversalPrinterWillHandleResponsesWithUnknownContentType()
    {
        $message = 'I have a response on me';
        $body = 'I am the body';

        $this
            ->logger
            ->error(Argument::type('string'))
            ->shouldBeCalledTimes(1);

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
            ->willReturn('unknown content type');

        $this
            ->jsonPrinter
            ->printIt(Argument::exact($body), Argument::exact(LogLevel::ERROR))
            ->shouldNotBeCalled();

        $this
            ->universalPrinter
            ->printIt(Argument::exact($body), Argument::exact(LogLevel::ERROR))
            ->shouldBeCalledTimes(1);

        $responseLessException = new RequestException($message, $request->reveal(), $response->reveal());

        $this
            ->handler
            ->handle($responseLessException);
    }

}
