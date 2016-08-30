<?php

namespace Hmaus\Spas\Request;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\UriTemplate;
use Hmaus\Spas\Event\HttpTransaction;
use Hmaus\Spas\Validator\ValidatorService;
use Hmaus\SpasParser\ParsedRequest;
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
        Logger $logger,
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
        $request->setBaseUrl($this->input->getOption('base_uri'));

        $this->dispatcher->dispatch(HttpTransaction::NAME, new HttpTransaction($request));

        if ($request->isEnabled()) {
            try {
                // expand uri using params
                $request->setHref(
                    (new UriTemplate())->expand($request->getHref(), $request->getParams()->all())
                );

                $this->logger->info($request->getName());
                $this->logger->info(sprintf('[%s] %s', $request->getMethod(), $request->getHref()));

                $response = $this->http->request($request);
                // todo I guess here would be the right spot to look at repetition for polling
                // todo event listeners could flag the request as to be repeated

                $this->validator->validate($request, $response);

                if ($this->validator->isValid()) {
                    $this->logger->info('success');
                }
                else {
                    $this->logger->error('fail');

                    $report = $this->validator->getReport();
                    foreach ($report as $validatorName => $result) {
                        if ($result) continue;
                        $this->logger->error(
                            sprintf('%s: %s', $validatorName, 'fail')
                        );
                    }
                }

                $this->validator->reset();
            } catch (ClientException $clientException) {
                $this->logger->error($clientException->getMessage());
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
            }
        } else {
            $this->logger->notice(
                sprintf('Request "%s" disabled by hook', $request->getName())
            );
        }


    }
}