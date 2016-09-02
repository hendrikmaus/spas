<?php

namespace Hmaus\Spas\Request;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\UriTemplate;
use Hmaus\Spas\Event\HttpTransaction;
use Hmaus\Spas\Json\JsonIndenter;
use Hmaus\Spas\Validator\ValidatorService;
use Hmaus\SpasParser\ParsedRequest;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Logger;
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
     * @var Logger
     */
    private $logger;

    public function __construct(
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher,
        HttpClient $http,
        ValidatorService $validator,
        Filesystem $filesystem
    ) {
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->http = $http;
        $this->validator = $validator;
        $this->filesystem = $filesystem;
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

        $filters = $this->input->getOption('filter');

        if ($filters) {
            if (!in_array($request->getName(), $filters)) {
                $request->setEnabled(false);
            }
        }

        $request->setBaseUrl($this->input->getOption('base_uri'));

        $request->setHref(
            (new UriTemplate())->expand($request->getHref(), $request->getParams()->all())
        );

        if ($request->isEnabled()) {
            try {
                $this->dispatcher->dispatch(HttpTransaction::NAME, new HttpTransaction($request));

                $this->logger->info(
                    sprintf(
                        '%s %s...',
                        $request->getMethod(),
                        substr($request->getHref(), 0, 80) // todo disbale that with an option?
                    )
                );

                $response = $this->http->request($request);

                $this->logger->info(
                    sprintf('%d %s', $response->getStatusCode(), $response->getReasonPhrase())
                );
                // todo I guess here would be the right spot to look at repetition for polling
                // todo event listeners could flag the request as to be repeated

                $this->validator->validate($request, $response);

                if (!$this->validator->isValid()) {
                    $this->printValidationErrors();
                }

                $this->validator->reset();
            } catch (RequestException $requestException) {
                $this->handleRequestException($requestException);
            } catch (\Exception $exception) {
                // todo think of something better than justing printing the message
                $this->logger->error($exception->getMessage());
            }
        } else {
            $this->logger->info(
                sprintf('Disabled', $request->getName())
            );
        }

    }

    private function handleRequestException(RequestException $requestException)
    {
        $response = $requestException->getResponse();

        if (!$response) {
            $this->logger->error($requestException->getMessage());

            return;
        }

        $this->logger->error(
            sprintf('%d %s', $response->getStatusCode(), $response->getReasonPhrase())
        );

        $body = $response->getBody()->getContents();

        if (!$body) {
            return;
        }

        $contentType = $response->getHeaderLine('content-type');

        if (!$contentType) {
            return;
        }

        if (strpos($contentType, 'json') !== false) {
            $this->prettyPrintJson($body);

            return;
        }

        // todo add support for other content types as well
    }

    /**
     * todo create a common interface for the error printers
     * todo create concrete implementation for printers
     * @param $body
     */
    private function prettyPrintJson($body)
    {
        $prettyBody = JsonIndenter::indent($body);

        $maxLen = 300; // todo configurable?
        if (strlen($prettyBody) > $maxLen) {
            $prettyBody = substr($prettyBody, 0, $maxLen);
            $prettyBody .= "\n\n(truncated)\n";
        }

        $this->logger->error("\n".$prettyBody);
    }

    private function printValidationErrors()
    {
        $report = $this->validator->getReport();
        foreach ($report as $validator) {
            if ($validator->isValid()) {
                continue;
            }

            $this->logger->error(
                sprintf('%s failed with:', $validator->getName())
            );
            $this->logger->error('');

            $errors = $validator->getErrors();

            // todo how will this look with the plaintext validator errors?

            foreach ($errors as $error) {
                $this->logger->error(
                    sprintf(' Property: %s', $error->property)
                );
                $this->logger->error(
                    sprintf('  Message: %s', $error->message)
                );
                $this->logger->error('');
            }
        }
    }

}
