<?php

namespace Hmaus\Spas\Tests\Request;

use GuzzleHttp\Psr7\Response;
use Hmaus\Spas\Event\AfterEach;
use Hmaus\Spas\Event\BeforeEach;
use Hmaus\Spas\Formatter\Formatter;
use Hmaus\Spas\Formatter\FormatterService;
use Hmaus\Spas\Parser\Options\Repetition;
use Hmaus\Spas\Parser\SpasResponse;
use Hmaus\Spas\Request\FilterHandler;
use Hmaus\Spas\Request\HttpClient;
use Hmaus\Spas\Request\RequestProcessor;
use Hmaus\Spas\Request\Result\ExceptionHandler;
use Hmaus\Spas\Request\Result\ProcessorReport;
use Hmaus\Spas\Validation\ValidatorService;
use Hmaus\Spas\Parser\SpasRequest;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

        $this
            ->input
            ->getOption('all_transactions')
            ->willReturn(null);

        $this
            ->input
            ->getOption('polling_count')
            ->willReturn(3);

        $this->requestProcessor = new RequestProcessor(
            $this->input->reveal(),
            $this->logger->reveal(),
            $this->dispatcher->reveal(),
            $this->validatorService->reveal(),
            $this->httpClient->reveal(),
            $this->exceptionHandler->reveal(),
            $this->formatterService->reveal(),
            $this->filterHandler->reveal(),
            new ProcessorReport()
        );
    }

    public function testHappyCase()
    {
        $request = new SpasRequest();
        $request->setName('Group > Resource > Action');
        $request->setHref('/health');
        $request->setEnabled(true);

        $response = new SpasResponse();
        $request->setExpectedResponse($response);

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

        $httpresponse = new Response();

        $this
            ->httpClient
            ->request($request)
            ->willReturn($httpresponse)
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->validate($request)
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->isValid()
            ->willReturn(true)
            ->shouldBeCalledTimes(2);

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

        $response = new SpasResponse();
        $request->setExpectedResponse($response);

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

        $response
            ->getStatusCode()
            ->willReturn(200);

        $response
            ->getReasonPhrase()
            ->willReturn('OK');

        $response
            ->hasHeader(Argument::any())
            ->willReturn(false);

        $response
            ->getHeaders()
            ->willReturn([
                'retry-after'  => ['100'],
                'content-type' => 'application/json'
            ]);

        $body = $this->prophesize(StreamInterface::class);
        $body
            ->getContents()
            ->willReturn('{"error":"here"}');

        $response
            ->getBody()
            ->willReturn($body->reveal());

        $this
            ->httpClient
            ->request($request)
            ->willReturn($response->reveal())
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->validate($request)
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->isValid()
            ->willReturn(false)
            ->shouldBeCalledTimes(2);

        $contentType = 'validator';
        $this
            ->validatorService
            ->getContentType()
            ->willReturn($contentType)
            ->shouldBeCalledTimes(1);

        $formatter = $this->prophesize(Formatter::class);
        $formatter
            ->format(Argument::any())
            ->willReturn('Formatted Message');

        $this
            ->formatterService
            ->getFormatterByContentType(Argument::any())
            ->willReturn($formatter->reveal())
            ->shouldBeCalledTimes(2);

        $this
            ->validatorService
            ->getReport();

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

        $report = $this->requestProcessor->getReport();
        $this->assertSame(1, $report->getFailed());
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

    public function testPollsWhenRetryAfterHeaderIsRecognized()
    {
        $request = new SpasRequest();
        $request->setName('Group > Resource > Action');
        $request->setHref('/health');
        $request->setEnabled(true);

        $response = new SpasResponse();
        $request->setExpectedResponse($response);

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
        $response
            ->hasHeader('retry-after')
            ->willReturn(true);

        $response
            ->getHeader('retry-after')
            ->willReturn([0]); // values are an array on there

        $response
            ->getHeaders()
            ->willReturn(['retry-after' => ['100']]);

        $response
            ->getStatusCode()
            ->willReturn(200);

        $response
            ->getReasonPhrase()
            ->willReturn('OK');

        $body = $this->prophesize(StreamInterface::class);
        $body
            ->getContents()
            ->willReturn('{"error":"here"}');

        $response
            ->getBody()
            ->willReturn($body->reveal());

        $this
            ->httpClient
            ->request($request)
            ->willReturn($response->reveal())
            ->shouldBeCalledTimes(3);

        $this
            ->validatorService
            ->validate($request)
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->isValid()
            ->willReturn(true)
            ->shouldBeCalledTimes(2);

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

    public function testAbortPollingIfRetryAfterIsTooLarge()
    {
        $request = new SpasRequest();
        $request->setName('Group > Resource > Action');
        $request->setHref('/health');
        $request->setEnabled(true);

        $response = new SpasResponse();
        $request->setExpectedResponse($response);

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
        $response
            ->hasHeader('retry-after')
            ->willReturn(true);

        $response
            ->getHeader('retry-after')
            ->willReturn([100]); // values are an array on there

        $response
            ->getHeaders()
            ->willReturn(['retry-after' => ['100']]);

        $response
            ->getStatusCode()
            ->willReturn(200);

        $response
            ->getReasonPhrase()
            ->willReturn('OK');

        $body = $this->prophesize(StreamInterface::class);
        $body
            ->getContents()
            ->willReturn('{"error":"here"}');

        $response
            ->getBody()
            ->willReturn($body->reveal());

        $this
            ->httpClient
            ->request($request)
            ->willReturn($response->reveal())
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->validate($request)
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->isValid()
            ->willReturn(true)
            ->shouldBeCalledTimes(2);

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

    public function testAbortPollingIfRetryAfterIsNotNumeric()
    {
        $request = new SpasRequest();
        $request->setName('Group > Resource > Action');
        $request->setHref('/health');
        $request->setEnabled(true);

        $response = new SpasResponse();
        $request->setExpectedResponse($response);

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
        $response
            ->hasHeader('retry-after')
            ->willReturn(true);

        $response
            ->getHeader('retry-after')
            ->willReturn(['2016-11-11']); // values are an array on there

        $response
            ->getHeaders()
            ->willReturn(['retry-after' => ['2016-11-11']]);

        $response
            ->getStatusCode()
            ->willReturn(200);

        $response
            ->getReasonPhrase()
            ->willReturn('OK');

        $body = $this->prophesize(StreamInterface::class);
        $body
            ->getContents()
            ->willReturn('{"error":"here"}');

        $response
            ->getBody()
            ->willReturn($body->reveal());

        $this
            ->httpClient
            ->request($request)
            ->willReturn($response->reveal())
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->validate($request)
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->isValid()
            ->willReturn(true)
            ->shouldBeCalledTimes(2);

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

    public function testCanRepeatRequests()
    {
        $request = new SpasRequest();
        $request->setName('Group > Resource > Action');
        $request->setHref('/health');
        $request->setEnabled(true);

        $repetitionConfig = new Repetition();
        $repetitionConfig->repeat = true;
        $repetitionConfig->times = 1;
        $repetitionConfig->count = 0;

        $request->setRepetitionConfig($repetitionConfig);

        $response = new SpasResponse();
        $request->setExpectedResponse($response);

        $baseUrl = 'http://example.com';
        $this
            ->input
            ->getOption('base_uri')
            ->willReturn($baseUrl);

        $this
            ->dispatcher
            ->dispatch(BeforeEach::NAME, Argument::type(BeforeEach::class))
            ->shouldBeCalledTimes(2);

        $this
            ->filterHandler
            ->filter($request)
            ->shouldBeCalledTimes(2);

        $httpresponse = new Response();

        $this
            ->httpClient
            ->request($request)
            ->willReturn($httpresponse)
            ->shouldBeCalledTimes(2);

        $this
            ->validatorService
            ->validate($request)
            ->shouldBeCalledTimes(2);

        $this
            ->validatorService
            ->isValid()
            ->willReturn(true)
            ->shouldBeCalledTimes(4);

        $this
            ->validatorService
            ->reset()
            ->shouldBeCalledTimes(2);

        $this
            ->dispatcher
            ->dispatch(AfterEach::NAME, Argument::type(AfterEach::class))
            ->shouldBeCalledTimes(2);

        $this
            ->requestProcessor
            ->process(
                $request
            );
    }

    public function testShouldOnlyRunHappyCaseTransactions()
    {
        $request = new SpasRequest();
        $request->setName('Group > Resource > Action');
        $request->setHref('/health');
        $request->setEnabled(true);

        $response = new SpasResponse();
        $request->setExpectedResponse($response);

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

        $httpresponse = new Response();

        $this
            ->httpClient
            ->request($request)
            ->willReturn($httpresponse)
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->validate($request)
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->isValid()
            ->willReturn(true)
            ->shouldBeCalledTimes(2);

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

        // run the same request a second time, so the processor will immediately exit
        // the test asserts the behaviour as it sets "shouldBeCalledTimes" above
        $this
            ->requestProcessor
            ->process(
                $request
            );
    }
}
