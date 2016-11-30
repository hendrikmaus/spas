<?php

namespace Hmaus\Spas\Request;

use Hmaus\Spas\Event\AfterAll;
use Hmaus\Spas\Event\BeforeAll;
use Hmaus\Spas\Parser\ParsedRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Executor
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var RequestProcessor
     */
    private $requestProcessor;

    /**
     * @var HookHandler
     */
    private $hookHandler;

    public function __construct(
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher,
        RequestProcessor $requestProcessor,
        HookHandler $hookHandler
    )
    {
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->requestProcessor = $requestProcessor;
        $this->hookHandler = $hookHandler;
    }

    /**
     * @param ParsedRequest[] $requests
     * @return boolean
     */
    public function run(array $requests)
    {
        $this->hookHandler->includeHooks();
        $this->dispatcher->dispatch(BeforeAll::NAME, new BeforeAll($requests));

        foreach ($requests as $request) {
            $this->requestProcessor->process($request);
        }

        $this->logger->info('');
        $this->dispatcher->dispatch(AfterAll::NAME, new AfterAll($requests));

        $report = $this->requestProcessor->getReport();

        $this->logger->info('-----------------');
        $this->logger->info(
            'Passed: {0}, Failed: {1}, Disabled: {2}', [
            $report->getPassed(),
            $report->getFailed(),
            $report->getDisabled()
        ]);

        return !$report->hasFailures();
    }
}
