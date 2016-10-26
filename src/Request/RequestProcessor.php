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

            $response = $this->http->request($request);
            $this->printResponse($response);
            // todo I guess here would be the right spot to look at repetition for polling
            // todo event listeners could flag the request as to be repeated

            // todo event BeforeValidation
            $this->validator->validate($request, $response);
            // todo event AfterValidation
            $this->printValidatorReport();
            $this->validator->reset();
        } catch (\Exception $exception) {
            // todo event Exception
            $this->exceptionHandler->handle($exception);
        }

        $this->dispatcher->dispatch(AfterEach::NAME, new AfterEach($request));
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
