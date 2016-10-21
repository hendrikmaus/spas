<?php

namespace Hmaus\Spas\Tests\Request;

use Hmaus\Spas\Request\HookHandler;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

class HookHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InputInterface|ObjectProphecy
     */
    private $input;

    /**
     * @var EventDispatcherInterface|ObjectProphecy
     */
    private $dispatcher;

    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $logger;

    /**
     * @var Filesystem|ObjectProphecy
     */
    private $filesystem;

    /**
     * @var HookHandler
     */
    private $hookHandler;

    protected function setUp()
    {
        $this->input = $this->prophesize(InputInterface::class);
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->filesystem = $this->prophesize(Filesystem::class);

        $this->hookHandler = new HookHandler(
            $this->input->reveal(),
            $this->dispatcher->reveal(),
            $this->logger->reveal(),
            $this->filesystem->reveal()
        );
    }

    public function testCanProvideEventDispatcher()
    {
        $this->assertInstanceOf(EventDispatcherInterface::class, $this->hookHandler->getDispatcher());
    }

    public function testCanProvideConsoleLogger()
    {
        $this->assertInstanceOf(LoggerInterface::class, $this->hookHandler->getLogger());
    }

    public function testCanProvideHookDataWhenItIsNull()
    {
        $this
            ->input
            ->getOption('hook_data')
            ->willReturn(null)
            ->shouldBeCalledTimes(1);

        $actual = $this
            ->hookHandler
            ->getHookData();

        $this->assertEmpty($actual);

        /*
         * the object should return the same when I call the method a second time
         * the input object, though, will only receive one call
         */
        $actual = $this
            ->hookHandler
            ->getHookData();

        $this->assertEmpty($actual);
    }

    public function testCanProvideHookData()
    {
        $hookData = '{"some":"data"}';

        $this
            ->input
            ->getOption('hook_data')
            ->willReturn($hookData)
            ->shouldBeCalledTimes(1);

        $actual = $this
            ->hookHandler
            ->getHookData();

        $this->assertSame($hookData, $actual);

        /*
         * the object should return the same when I call the method a second time
         * the input object, though, will only receive one call
         */
        $actual = $this
            ->hookHandler
            ->getHookData();

        $this->assertSame($hookData, $actual);
    }

    public function testCanDetectNoHookFilesInCommand()
    {
        $this
            ->input
            ->getOption('hook')
            ->willReturn([])
            ->shouldBeCalledTimes(1);

        $actual = $this
            ->hookHandler
            ->getHookFiles();

        $this->assertSame([], $actual);
    }

    public function testCanDetectNoHookFiles()
    {
        $hooks = ['hook1', 'hook2'];

        $this
            ->input
            ->getOption('hook')
            ->willReturn($hooks)
            ->shouldBeCalledTimes(1);

        $actual = $this
            ->hookHandler
            ->getHookFiles();

        $this->assertSame($hooks, $actual);
    }

    public function testCanDetectNonExistingHookFile()
    {
        $hooks = ['I am not existing'];

        $this
            ->input
            ->getOption('hook')
            ->willReturn($hooks)
            ->shouldBeCalledTimes(1);

        $this
            ->logger
            ->warning(Argument::type('string'))
            ->shouldBeCalled();

        $this
            ->hookHandler
            ->includeHooks();
    }

    public function testCanIncludeHookFiles()
    {
        $hooks = [__DIR__ . '/../fixtures/include-file-for-hookhandler.php'];

        $this
            ->input
            ->getOption('hook')
            ->willReturn($hooks)
            ->shouldBeCalledTimes(1);

        $this
            ->filesystem
            ->exists(Argument::exact($hooks[0]))
            ->willReturn(true);

        $this
            ->hookHandler
            ->includeHooks();

        $this->assertTrue($GLOBALS['spas.hooks.loaded']);
    }

}
