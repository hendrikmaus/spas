<?php

namespace Hmaus\Spas\Tests\Request;

use GuzzleHttp\Psr7\Response;
use Hmaus\Spas\Event\AfterEach;
use Hmaus\Spas\Event\BeforeEach;
use Hmaus\Spas\Formatter\FormatterService;
use Hmaus\Spas\Formatter\ValidationErrorFormatter;
use Hmaus\Spas\Request\FilterHandler;
use Hmaus\Spas\Request\HttpClient;
use Hmaus\Spas\Request\RequestProcessor;
use Hmaus\Spas\Request\Result\ExceptionHandler;
use Hmaus\Spas\Validation\ValidatorService;
use Hmaus\SpasParser\ParsedRequest;
use Hmaus\SpasParser\SpasRequest;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class RequestProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InputInterface|ObjectProphecy
     */
    private $input;

    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $logger;

    /**
     * @var EventDispatcherInterface|ObjectProphecy
     */
    private $dispatcher;

    /**
     * @var ValidatorService|ObjectProphecy
     */
    private $validatorService;

    /**
     * @var HttpClient|ObjectProphecy
     */
    private $httpClient;

    /**
     * @var ExceptionHandler|ObjectProphecy
     */
    private $exceptionHandler;

    /**
     * @var FormatterService|ObjectProphecy
     */
    private $formatterService;

    /**
     * @var FilterHandler|ObjectProphecy
     */
    private $filterHandler;

    /**
     * @var RequestProcessor
     */
    private $requestProcessor;

    protected function setUp()
    {
        $this->input = $this->prophesize(InputInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->validatorService = $this->prophesize(ValidatorService::class);
        $this->httpClient = $this->prophesize(HttpClient::class);
        $this->exceptionHandler = $this->prophesize(ExceptionHandler::class);
        $this->formatterService = $this->prophesize(FormatterService::class);
        $this->filterHandler = $this->prophesize(FilterHandler::class);

        $this->requestProcessor = new RequestProcessor(
            $this->input->reveal(),
            $this->logger->reveal(),
            $this->dispatcher->reveal(),
            $this->validatorService->reveal(),
            $this->httpClient->reveal(),
            $this->exceptionHandler->reveal(),
            $this->formatterService->reveal(),
            $this->filterHandler->reveal()
        );
    }

    public function testHappyCase()
    {
        $request = new SpasRequest();
        $request->setName('Group > Resource > Action');
        $request->setHref('/health');
        $request->setEnabled(true);

        $baseUrl = 'http://example.com';
        $this
            ->input
            ->getOption('base_uri')
            ->willReturn($baseUrl);

        $this
            ->dispatcher
            ->dispatch(BeforeEach::NAME, Argument::type(BeforeEach::class))
            ->shouldBeCalledTimes(1);

        $this
            ->filterHandler
            ->filter($request)
            ->shouldBeCalledTimes(1);

        $response = $this->prophesize(Response::class);

        $this
            ->httpClient
            ->request($request)
            ->willReturn($response->reveal())
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->validate(
                $request,
                $response->reveal()
            )
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->isValid()
            ->willReturn(true)
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->reset()
            ->shouldBeCalledTimes(1);

        $this
            ->dispatcher
            ->dispatch(AfterEach::NAME, Argument::type(AfterEach::class))
            ->shouldBeCalledTimes(1);

        $this
            ->requestProcessor
            ->process(
                $request
            );
    }

    public function testValidationReportGetsCreatedForErrors()
    {
        $request = new SpasRequest();
        $request->setName('Group > Resource > Action');
        $request->setHref('/health');
        $request->setEnabled(true);

        $baseUrl = 'http://example.com';
        $this
            ->input
            ->getOption('base_uri')
            ->willReturn($baseUrl);

        $this
            ->dispatcher
            ->dispatch(BeforeEach::NAME, Argument::type(BeforeEach::class))
            ->shouldBeCalledTimes(1);

        $this
            ->filterHandler
            ->filter($request)
            ->shouldBeCalledTimes(1);

        $response = $this->prophesize(Response::class);

        $this
            ->httpClient
            ->request($request)
            ->willReturn($response->reveal())
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->validate(
                $request,
                $response->reveal()
            )
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->isValid()
            ->willReturn(false)
            ->shouldBeCalledTimes(1);

        $contentType = 'validator';
        $this
            ->validatorService
            ->getContentType()
            ->willReturn($contentType)
            ->shouldBeCalledTimes(1);

        $formatter = new ValidationErrorFormatter();

        $this
            ->formatterService
            ->getFormatterByContentType($contentType)
            ->willReturn($formatter)
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->getReport()
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->reset()
            ->shouldBeCalledTimes(1);

        $this
            ->dispatcher
            ->dispatch(AfterEach::NAME, Argument::type(AfterEach::class))
            ->shouldBeCalledTimes(1);

        $this
            ->requestProcessor
            ->process(
                $request
            );
    }

    public function testRequestsCanBeDisabled()
    {
        $request = new SpasRequest();
        $request->setName('Group > Resource > Action');
        $request->setHref('/health');
        $request->setEnabled(false);

        $baseUrl = 'http://example.com';
        $this
            ->input
            ->getOption('base_uri')
            ->willReturn($baseUrl);

        $this
            ->dispatcher
            ->dispatch(BeforeEach::NAME, Argument::type(BeforeEach::class))
            ->shouldBeCalledTimes(1);

        $this
            ->filterHandler
            ->filter($request)
            ->shouldBeCalledTimes(1);

        $response = $this->prophesize(Response::class);

        $this
            ->httpClient
            ->request($request)
            ->willReturn($response->reveal())
            ->shouldNotBeCalled();

        $this
            ->dispatcher
            ->dispatch(AfterEach::NAME, Argument::type(AfterEach::class))
            ->shouldBeCalledTimes(1);

        $this
            ->requestProcessor
            ->process(
                $request
            );
    }

    public function testCanHandleExceptions()
    {
        $request = new SpasRequest();
        $request->setName('Group > Resource > Action');
        $request->setHref('/health');
        $request->setEnabled(true);

        $baseUrl = 'http://example.com';
        $this
            ->input
            ->getOption('base_uri')
            ->willReturn($baseUrl);

        $this
            ->dispatcher
            ->dispatch(BeforeEach::NAME, Argument::type(BeforeEach::class))
            ->shouldBeCalledTimes(1);

        $this
            ->filterHandler
            ->filter($request)
            ->shouldBeCalledTimes(1);

        $this
            ->httpClient
            ->request($request)
            ->willThrow(new \Exception());

        $this
            ->exceptionHandler
            ->handle(Argument::type(\Exception::class))
            ->shouldBeCalledTimes(1);

        $this
            ->dispatcher
            ->dispatch(AfterEach::NAME, Argument::type(AfterEach::class))
            ->shouldBeCalledTimes(1);

        $this
            ->requestProcessor
            ->process(
                $request
            );
    }
}
