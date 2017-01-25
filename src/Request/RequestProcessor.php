<?php

namespace Hmaus\Spas\Request;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\UriTemplate;
use Hmaus\Spas\Event\AfterEach;
use Hmaus\Spas\Event\BeforeEach;
use Hmaus\Spas\Formatter\FormatterService;
use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\Parser\SpasResponse;
use Hmaus\Spas\Request\Result\ExceptionHandler;
use Hmaus\Spas\Request\Result\ProcessorReport;
use Hmaus\Spas\Validation\ValidatorService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\HeaderBag;

class RequestProcessor
{
    /**
     * Amount of max seconds spas will wait until re-triggering a pollabe resource
     * @type int
     */
    const RETRY_AFTER_THRESHHOLD = 2;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var ValidatorService
     */
    private $validator;

    /**
     * @var HttpClient
     */
    private $http;

    /**
     * @var ExceptionHandler
     */
    private $exceptionHandler;

    /**
     * @var FormatterService
     */
    private $formatterService;

    /**
     * @var FilterHandler
     */
    private $filterHandler;

    /**
     * @var ProcessorReport
     */
    private $report;

    public function __construct(
        InputInterface $input,
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher,
        ValidatorService $validatorService,
        HttpClient $http,
        ExceptionHandler $exceptionHandler,
        FormatterService $formatterService,
        FilterHandler $filterHandler,
        ProcessorReport $processorReport
    )
    {
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->validator = $validatorService;
        $this->http = $http;
        $this->exceptionHandler = $exceptionHandler;
        $this->formatterService = $formatterService;
        $this->input = $input;
        $this->filterHandler = $filterHandler;
        $this->report = $processorReport;
    }

    /**
     * Process each individual transaction
     * @param ParsedRequest $request
     */
    public function process(ParsedRequest $request)
    {
        if ($this->shouldExit($request)) {
            return;
        }

        $this->beginLogBlock($request);
        $this->dispatcher->dispatch(BeforeEach::NAME, new BeforeEach($request));
        $this->filterHandler->filter($request);
        $request->setBaseUrl($this->input->getOption('base_uri'));
        $request->setHref((new UriTemplate())->expand($request->getUriTemplate(), $request->getParams()->all()));

        if (!$request->isEnabled()) {
            $this->report->disabled();
            $this->logger->info('Disabled');
            $this->dispatcher->dispatch(AfterEach::NAME, new AfterEach($request));
            $this->endLogBlock();
            return;
        }

        try {
            $this->printRequest($request);
            $this->addActualResponse($this->doRequest($request), $request);
            $this->dispatcher->dispatch(AfterEach::NAME, new AfterEach($request));
            $this->validator->validate($request);
            $this->printErrorResponse($request);
            $this->printValidatorReport();
            $this->validator->reset();
        } catch (\Exception $exception) {
            $this->dispatcher->dispatch(AfterEach::NAME, new AfterEach($request));
            $this->report->failed();
            $this->exceptionHandler->handle($exception);
        }

        $this->checkforRepetition($request);
        $this->report->processed($request->getName());
    }

    /**
     * Report getter
     * @return ProcessorReport
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * Do request and handle polling for resources that return HTTP Retry-After header
     *
     * @param ParsedRequest $request
     * @param int $attempt How often the request was repeated
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function doRequest(ParsedRequest $request, $attempt = 0)
    {
        $attempt++;

        $pollingThreshhold = $this->input->getOption('polling_count') - 1;

        $response = $this->http->request($request);
        $this->printResponse($response);

        if (!$response->hasHeader('retry-after')) {
            return $response;
        }

        if ($attempt > $pollingThreshhold) {
            $this->logger->warning(
                'Retried {0} time(s), validating last response',
                [$pollingThreshhold + 1]
            );
            return $response;
        }

        $retryAfter = $response->getHeader('retry-after');
        $retryAfter = array_pop($retryAfter);

        if (!is_numeric($retryAfter)) {
            // todo spas could try to work with dates
            $this->logger->warning('Retry-After header contains a value spas cannot work with:');
            $this->logger->warning('  {0}', [$retryAfter]);
            $this->logger->warning('Validating last response');

            return $response;
        }

        $retryAfter = (int)$retryAfter;

        if ($retryAfter > static::RETRY_AFTER_THRESHHOLD) {
            $this->logger->warning(
                'Retry-After header wants to wait longer than spas\' threshhold of %d seconds', [
                static::RETRY_AFTER_THRESHHOLD
            ]);
            return $response;
        }

        $this->logger->info('');
        $this->logger->info('Waiting {0} second(s) until next try', [$retryAfter]);

        usleep($retryAfter * 1000000);

        return $this->doRequest($request, $attempt);
    }

    /**
     * @param $response
     * @codeCoverageIgnore
     */
    private function printResponse(Response $response)
    {
        $this->logger->info('{0} {1}', [$response->getStatusCode(), $response->getReasonPhrase()]);
    }

    /**
     * @param ParsedRequest $request
     * @codeCoverageIgnore
     */
    private function printRequest(ParsedRequest $request)
    {
        $maxPrintLength = 70;

        if (strlen($request->getHref()) > $maxPrintLength) {
            $this->logger->info('{0} {1}...', [
                $request->getMethod(),
                substr($request->getHref(), 0, $maxPrintLength)
            ]);
        } else {
            $this->logger->info('{0} {1}', [
                $request->getMethod(),
                $request->getHref()
            ]);
        }
    }

    /**
     * Log validation result to the console
     */
    private function printValidatorReport()
    {
        if (!$this->validator->isValid()) {
            $this->report->failed();

            $formatter = $this
                ->formatterService
                ->getFormatterByContentType(
                    $this->validator->getContentType()
                );

            $this->logger->error(
                $formatter->format(
                    $this->validator->getReport()
                )
            );
        } else {
            $this->report->passed();
            $this->logger->info('Passed');
        }
    }

    /**
     * If the validator found issues, this helper prints what the server has to say
     *
     * @param ParsedRequest $request
     */
    private function printErrorResponse(ParsedRequest $request)
    {
        if ($this->validator->isValid()) {
            return;
        }

        $response    = $request->getActualResponse();
        $contentType = $response->getHeaders()->get('content-type');
        $body        = $response->getBody();
        $formatter   = $this->formatterService->getFormatterByContentType($contentType);

        $this
            ->logger
            ->error(
                $formatter->format($body)
            );
    }

    /**
     * Print the visual start of a request being processed
     * @param ParsedRequest $request
     */
    private function beginLogBlock(ParsedRequest $request)
    {
        $this->logger->info('');
        $this->logger->info('-----------------');
        $this->logger->info($request->getName());
    }

    /**
     * Print the visual end of a processed request
     */
    private function endLogBlock()
    {
        $this->logger->info('-----------------');
    }

    /**
     * @param ParsedRequest $request
     */
    private function checkforRepetition(ParsedRequest $request)
    {
        $repetitionConfig = $request->getRepetitionConfig();
        $shouldRepeat     = $repetitionConfig->repeat;
        $repeatedEnough   = $repetitionConfig->count >= $repetitionConfig->times;

        if (!$shouldRepeat) {
            $this->endLogBlock();
            return;
        }

        if (!$repeatedEnough) {
            $repetitionConfig->count += 1;
            $this->logger->info('Repeating request');
            $this->endLogBlock();
            $this->process($request);
            return;
        }

        $this->endLogBlock();
    }

    /**
     * @param ParsedRequest $request
     * @return bool
     */
    private function shouldExit(ParsedRequest $request): bool
    {
        $wasProcessed = $this->report->wasProcessed($request->getName());
        $onlyRunHappyCaseTransactions = $this->input->getOption('all_transactions') == null;

        return $wasProcessed && $onlyRunHappyCaseTransactions;
    }

    /**
     * Map guzzle response to a ParsedResponse and add it to the request
     * This way, hooks can work with the results of a request
     * @param ResponseInterface $response
     * @param ParsedRequest $request
     */
    private function addActualResponse(ResponseInterface $response, ParsedRequest $request)
    {
        $parsedResponse = new SpasResponse();
        $parsedResponse->setBody($response->getBody()->getContents());
        $parsedResponse->setHeaders(new HeaderBag($response->getHeaders()));
        $parsedResponse->setStatusCode($response->getStatusCode());
        $parsedResponse->setReasonPhrase($response->getReasonPhrase());

        $request->setActualResponse($parsedResponse);
    }
}
