<?php

namespace Hmaus\Spas\Request;

use GuzzleHttp\UriTemplate;
use Hmaus\Spas\Event\AfterEach;
use Hmaus\Spas\Event\BeforeEach;
use Hmaus\Spas\Formatter\FormatterService;
use Hmaus\Spas\Request\Result\ExceptionHandler;
use Hmaus\Spas\Validation\ValidatorService;
use Hmaus\Spas\Parser\ParsedRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    public function __construct(
        InputInterface $input,
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher,
        ValidatorService $validatorService,
        HttpClient $http,
        ExceptionHandler $exceptionHandler,
        FormatterService $formatterService,
        FilterHandler $filterHandler
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
    }

    public function process(ParsedRequest $request)
    {
        $this->dispatcher->dispatch(BeforeEach::NAME, new BeforeEach($request));

        $this->logger->info($request->getName());

        // todo event BeforeFilter?
        $this->filterHandler->filter($request);
        // todo event AfterFilter?

        $request->setBaseUrl($this->input->getOption('base_uri'));

        // todo event BeforeUriExpansion?
        $request->setHref(
            (new UriTemplate())->expand($request->getHref(), $request->getParams()->all())
        );
        // todo event AfterUriExpansion?

        if (!$request->isEnabled()) {
            $this->logger->info('Disabled');
            $this->dispatcher->dispatch(AfterEach::NAME, new AfterEach($request));
            return;
        }

        try {
            $this->printRequest($request);

            $response = $this->doRequest($request);

            // todo event BeforeValidation
            $this->validator->validate($request, $response);
            // todo event AfterValidation

            $this->printValidatorReport();
            $this->validator->reset();
        }
        catch (\Exception $exception) {
            // todo event Exception
            $this->exceptionHandler->handle($exception);
        }

        $this->dispatcher->dispatch(AfterEach::NAME, new AfterEach($request));
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
                sprintf(
                    'Retried %d time(s), validating last response',
                    $pollingThreshhold + 1
                )
            );
            return $response;
        }

        $retryAfter = $response->getHeader('retry-after');
        $retryAfter = array_pop($retryAfter);

        if (!is_numeric($retryAfter)) {
            // todo spas could try to work with dates
            $this->logger->warning('Retry-After header contains a value spas cannot work with:');
            $this->logger->warning(sprintf('  %s', $retryAfter));
            $this->logger->warning('Validating last response');

            return $response;
        }

        $retryAfter = (int)$retryAfter;

        if ($retryAfter > static::RETRY_AFTER_THRESHHOLD) {
            $this->logger->warning(
                sprintf(
                    'Retry-After header wants to wait longer than spas\' threshhold of %d seconds',
                    static::RETRY_AFTER_THRESHHOLD
                )
            );
            return $response;
        }

        $this->logger->info('');
        $this->logger->info(sprintf('Waiting %d second(s) until next try', $retryAfter));

        usleep($retryAfter * 1000000);

        return $this->doRequest($request, $attempt);
    }

    /**
     * @param $response
     * @codeCoverageIgnore
     */
    private function printResponse($response)
    {
        $this->logger->info(
            sprintf('%d %s', $response->getStatusCode(), $response->getReasonPhrase())
        );
    }

    /**
     * @param ParsedRequest $request
     * @codeCoverageIgnore
     */
    private function printRequest(ParsedRequest $request)
    {
        $maxPrintLength = 70;

        if (strlen($request->getHref()) > $maxPrintLength) {
            $this->logger->info(
                sprintf(
                    '%s %s...',
                    $request->getMethod(),
                    substr($request->getHref(), 0, $maxPrintLength) // todo disbale that with an option?
                )
            );
        } else {
            $this->logger->info(
                sprintf(
                    '%s %s',
                    $request->getMethod(),
                    $request->getHref()
                )
            );
        }
    }

    /**
     * Log validation result to the console
     */
    private function printValidatorReport()
    {
        if (!$this->validator->isValid()) {
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
            $this->logger->info('Passed');
        }
    }
}
