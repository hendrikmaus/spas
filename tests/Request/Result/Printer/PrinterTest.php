<?php

namespace Hmaus\Spas\Tests\Request\Result\Printer;

use Hmaus\Spas\Request\Result\Printer\Printer;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class PrinterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Printer
     */
    private $printer;

    protected function setUp()
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->printer = new Printer($this->logger->reveal());
    }

    /**
     * @expectedException \Exception
     */
    public function testGetContentTypeThrowsException()
    {
        $this->printer->getContentType();
    }

    public function testCanPrintUntruncated()
    {
        $this->printer->setMaximumPrintLength(0);

        $data = '[';
        do {
            $data .= '{"key":"name","value":"testing"},';
        } while (strlen($data) < 200);
        $data .= ']';

        $this->assertTrue(strlen($data) > $this->printer->getMaximumPrintLength());

        $this->printer->printIt($data, LogLevel::ERROR);

        $this
            ->logger
            ->log(
                Argument::exact(LogLevel::ERROR),
                Argument::exact($data)
            )
            ->shouldBeCalledTimes(1);
    }

    public function testCanPrintTruncated()
    {
        $this->printer->setMaximumPrintLength(100);

        $data = '[';
        do {
            $data .= '{"key":"name","value":"testing"},';
        } while (strlen($data) < $this->printer->getMaximumPrintLength());
        $data .= ']';

        $this->assertTrue(strlen($data) > $this->printer->getMaximumPrintLength());

        $this->printer->printIt($data, LogLevel::ERROR);

        $this
            ->logger
            ->log(
                Argument::exact(LogLevel::ERROR),
                Argument::containingString('truncated')
            )
            ->shouldBeCalledTimes(1);
    }
}
