<?php

namespace Hmaus\Spas\Tests\Request;

use Hmaus\Spas\Request\HookHandler;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
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
            ->getRawHookData();

        $this->assertEmpty($actual);

        /*
         * the object should return the same when I call the method a second time
         * the input object, though, will only receive one call
         */
        $actual = $this
            ->hookHandler
            ->getRawHookData();

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
            ->getRawHookData();

        $this->assertSame($hookData, $actual);

        /*
         * the object should return the same when I call the method a second time
         * the input object, though, will only receive one call
         */
        $actual = $this
            ->hookHandler
            ->getRawHookData();

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
            ->warning(Argument::cetera())
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

    public function testCanProvideDataBag()
    {
        $bag = $this->hookHandler->getHookDataBag();
        $this->assertInstanceOf(ParameterBag::class, $bag);
        $this->assertCount(0, $bag->all());

        $newBag = new ParameterBag(['some' => 'thing']);
        $this->hookHandler->setHookDataBag($newBag);
        $this->assertSame($newBag, $this->hookHandler->getHookDataBag());
    }

    public function testCanApplyHookDataDefaults()
    {
        $this
            ->input
            ->getOption('hook_data')
            ->willReturn('{"hook-data":{"field1":true}}')
            ->shouldBeCalledTimes(1);

        $key = 'hook-data';

        $defaults = [
            'field1' => false,
            'field2' => false
        ];

        $data = $this->hookHandler->getHookDataFromJson();

        $expected = [
            'field1' => true,
            'field2' => false
        ];

        $this->assertSame(
            $expected,
            $this->hookHandler->applyHookDataDefaults($key, $defaults, $data)
        );
    }

    public function testCanApplyDefaults()
    {
        $key = 'hook-data';

        $defaults = [
            'field1' => false,
            'field2' => false
        ];

        $data = $this->hookHandler->getHookDataFromJson();

        $this->assertSame(
            $defaults,
            $this->hookHandler->applyHookDataDefaults($key, $defaults, $data)
        );
    }

    public function testCanGetHookdataFromJson()
    {
        $data = ['some' => 'thing'];

        $this
            ->input
            ->getOption('hook_data')
            ->willReturn(json_encode($data))
            ->shouldBeCalledTimes(1);

        $this
            ->logger
            ->error(Argument::cetera())
            ->shouldNotBeCalled();

        $this->assertSame($data, $this->hookHandler->getHookDataFromJson());
    }

    public function testLogsErrorWhenHookDataIsNotValidJson()
    {
        $this
            ->input
            ->getOption('hook_data')
            ->willReturn('{')
            ->shouldBeCalledTimes(1);

        $this
            ->logger
            ->error(Argument::containingString('Hook Handler: Passed hook data failed in json decoding process'), Argument::type('array'))
            ->shouldBeCalledTimes(1);

        $this->assertSame([], $this->hookHandler->getHookDataFromJson());
    }

}
