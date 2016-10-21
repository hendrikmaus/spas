<?php

namespace Hmaus\Spas\Request;

use GuzzleHttp\UriTemplate;
use Hmaus\Spas\Event\HttpTransaction;
use Hmaus\Spas\Formatter\FormatterService;
use Hmaus\Spas\Request\Result\ExceptionHandler;
use Hmaus\Spas\Validation\ValidatorService;
use Hmaus\SpasParser\ParsedRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
     * @var EventDispatcher
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
     * @var OutputInterface
     */
    private $output;

    /**
     * @var FilterHandler
     */
    private $filterHandler;

    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        LoggerInterface $logger,
        EventDispatcher $dispatcher,
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
        $this->output = $output;
        $this->filterHandler = $filterHandler;
    }

    public function process(ParsedRequest $request)
    {
        $this->logger->info($request->getName());

        $this->filterHandler->filter($request);

        $request->setBaseUrl($this->input->getOption('base_uri'));
        $request->setHref(
            (new UriTemplate())->expand($request->getHref(), $request->getParams()->all())
        );

        if (!$request->isEnabled()) {
            $this->printDisabled($request);
            return;
        }

        try {
            $this->dispatcher->dispatch(HttpTransaction::NAME, new HttpTransaction($request));
            $this->printRequest($request);

            $response = $this->http->request($request);
            $this->printResponse($response);
            // todo I guess here would be the right spot to look at repetition for polling
            // todo event listeners could flag the request as to be repeated

            $this->validator->validate($request, $response);
            $this->printValidatorReport();
            $this->validator->reset();
        } catch (\Exception $exception) {
            $this->exceptionHandler->handle($exception);
        }
    }

    /**
     * @param $response
     */
    private function printResponse($response)
    {
        $this->logger->info(
            sprintf('%d %s', $response->getStatusCode(), $response->getReasonPhrase())
        );
    }

    /**
     * @param ParsedRequest $request
     */
    private function printDisabled(ParsedRequest $request)
    {
        $this->logger->info(
            sprintf('Disabled', $request->getName())
        );
    }

    /**
     * @param ParsedRequest $request
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
