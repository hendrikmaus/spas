<?php

namespace Hmaus\Spas\Tests\Request;

use Hmaus\Spas\Request\Executor;
use Hmaus\Spas\Request\HookHandler;
use Hmaus\Spas\Request\RequestProcessor;
use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\Request\Result\ProcessorReport;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ExecutorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $logger;

    /**
     * @var EventDispatcherInterface|ObjectProphecy
     */
    private $dispatcher;

    /**
     * @var RequestProcessor|ObjectProphecy
     */
    private $requestProcessor;

    /**
     * @var HookHandler|ObjectProphecy
     */
    private $hookHandler;

    /**
     * @var Executor
     */
    private $executor;

    protected function setUp()
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->requestProcessor = $this->prophesize(RequestProcessor::class);
        $this->hookHandler = $this->prophesize(HookHandler::class);

        $this->executor = new Executor(
            $this->logger->reveal(),
            $this->dispatcher->reveal(),
            $this->requestProcessor->reveal(),
            $this->hookHandler->reveal()
        );
    }

    public function testRun()
    {
        $parsedRequest = $this->prophesize(ParsedRequest::class);

        $this
            ->hookHandler
            ->includeHooks()
            ->shouldBeCalledTimes(1);

        $this
            ->dispatcher
            ->dispatch(
                Argument::exact('hmaus.spas.event.before_all'),
                Argument::type(Event::class)
            )
            ->shouldBeCalledTimes(1);

        $this
            ->dispatcher
            ->dispatch(
                Argument::exact('hmaus.spas.event.after_all'),
                Argument::type(Event::class)
            )
            ->shouldBeCalledTimes(1);

        $this
            ->logger
            ->info(Argument::type('string'), Argument::cetera())
            ->shouldBeCalled();

        $this
            ->requestProcessor
            ->process(Argument::exact($parsedRequest->reveal()))
            ->shouldBeCalledTimes(1);

        $this
            ->requestProcessor
            ->getReport()
            ->willReturn(new ProcessorReport())
            ->shouldBeCalledTimes(1);

        $this
            ->executor
            ->run([$parsedRequest->reveal()]);
    }

}
