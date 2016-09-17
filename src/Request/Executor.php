<?php

namespace Hmaus\Spas\Request;

use GuzzleHttp\UriTemplate;
use Hmaus\Spas\Event\HttpTransaction;
use Hmaus\Spas\Request\Result\ExceptionHandler;
use Hmaus\Spas\Request\Result\Printer\ValidationReportPrinter;
use Hmaus\Spas\Validation\ValidatorService;
use Hmaus\SpasParser\ParsedRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

class Executor
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var HttpClient
     */
    private $http;

    /**
     * @var ValidatorService
     */
    private $validator;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ExceptionHandler
     */
    private $exceptionHandler;

    /**
     * Externally provided hook data
     * @var string
     */
    private $hookData = '';

    public function __construct(
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher,
        HttpClient $http,
        ValidatorService $validator,
        Filesystem $filesystem,
        ExceptionHandler $exceptionHandler
    ) {
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->http = $http;
        $this->validator = $validator;
        $this->filesystem = $filesystem;
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * @param ParsedRequest[] $requests
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function run(array $requests, InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->includeHooks();

        $this->dispatcher->dispatch('hmaus.spas.event.before_all', new Event());

        foreach ($requests as $request) {
            $this->logger->info('');
            $this->logger->info('-----------------');
            $this->process($request);
            $this->logger->info('-----------------');
        }
        $this->logger->info('');

        $this->dispatcher->dispatch('hmaus.spas.event.after_all', new Event());
    }

    /**
     * Looks for and parses the hook option
     */
    private function includeHooks()
    {
        // todo provide some isolation for the hooks; maybe not the best idea to run them inside the executor object

        $hookfiles = $this->input->getOption('hook');
        $hookdata  = $this->input->getOption('hook_data');

        if ($hookdata !== null) {
            $this->hookData = $hookdata;
        }

        if (count($hookfiles) === 0) {
            $this->logger->info('[INFO] No hooks loaded');

            return;
        }

        // create dispatcher variable for hook files loaded into this context
        /** @noinspection PhpUnusedLocalVariableInspection */
        $dispatcher = $this->dispatcher;

        foreach ($hookfiles as $hookfile) {
            if (!$this->filesystem->exists($hookfile)) {
                $this->logger->warning(sprintf('Hook file could not be loaded: %s', $hookfile));
                continue;
            }

            /** @noinspection PhpIncludeInspection */
            include $hookfile;
        }
    }

    /**
     * Fire request and pass on to the validator
     *
     * @param ParsedRequest $request
     */
    private function process(ParsedRequest $request)
    {
        $this->logger->info($request->getName());

        $this->applyFilters($request);

        $request->setBaseUrl($this->input->getOption('base_uri'));
        $request->setHref(
            (new UriTemplate())->expand($request->getHref(), $request->getParams()->all())
        );

        if(!$request->isEnabled()) {
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

    private function printValidatorReport()
    {
        if (!$this->validator->isValid()) {
            $printer = new ValidationReportPrinter($this->logger);
            $printer->print(
                $this->validator->getReport(),
                LogLevel::ERROR
            );
        }
        else {
            $this->logger->info('Passed');
        }
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
    private function applyFilters(ParsedRequest $request)
    {
        $filters = $this->input->getOption('filter');

        if ($filters) {
            if (!in_array($request->getName(), $filters)) {
                $request->setEnabled(false);
            }
        }
    }

    /**
     * Access to hook data in hook files
     *
     * @return string
     */
    public function getHookData()
    {
        return $this->hookData;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

}
