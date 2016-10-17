<?php

namespace Hmaus\Spas\Tests\Request\Result\Printer;

use Hmaus\Spas\Request\Result\Printer\Printer;
use Hmaus\Spas\Request\Result\Printer\UniversalPrinter;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class UniversalPrinterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $logger;

    /**
     * @var Printer|ObjectProphecy
     */
    private $printer;

    protected function setUp()
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->printer = new UniversalPrinter($this->logger->reveal());
    }

    public function testDoesKnowItsContentType()
    {
        $this->assertSame('null', $this->printer->getContentType());
    }

    public function testPrinterPrints()
    {
        $message = 'message to print';
        $this
            ->logger
            ->log(Argument::exact(LogLevel::ERROR), Argument::exact($message))
            ->shouldBeCalledTimes(1);

        $this->printer->printIt($message, LogLevel::ERROR);

    }
}
