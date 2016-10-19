<?php

namespace Hmaus\Spas\Tests\Request\Result\Printer;

use Hmaus\Spas\Request\Result\Printer\JsonPrinter;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class JsonPrinterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $logger;

    /**
     * @var JsonPrinter
     */
    private $printer;

    protected function setUp()
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->printer = new JsonPrinter($this->logger->reveal());
    }

    public function testCanPrintUntruncated()
    {
        $data = '{"hello":"world"}';
        $this->printer->printIt($data, LogLevel::ERROR);

        $expected = '{
    "hello":"world"
}';

        $this
            ->logger
            ->log(
                Argument::exact(LogLevel::ERROR),
                Argument::exact($expected)
            )
            ->shouldBeCalledTimes(1);
    }

    public function testCanPrintTruncated()
    {
        $data = '[';
        do {
            $data .= '{"key":"name","value":"testing"},';
        }
        while (strlen($data) < $this->printer->getMaximumPrintLength());
        $data .= ']';
        $this->assertTrue(strlen($data) > $this->printer->getMaximumPrintLength());

        $this->printer->setMaximumPrintLength(300);
        $this->printer->printIt($data, LogLevel::ERROR);

        $expected = '[
    {
        "key":"name",
        "value":"testing"
    },
    {
        "key":"name",
        "value":"testing"
    },
    {
        "key":"name",
        "value":"testing"
    },
    {
        "key":"name",
        "value":"testing"
    },
    {
        "key":"name",
        "value":"testing"


(truncated)
';

        $this
            ->logger
            ->log(
                Argument::exact(LogLevel::ERROR),
                Argument::exact($expected)
            )
            ->shouldBeCalledTimes(1);
    }

    public function testMaxPrintLengthCanBeSet()
    {
        $this->printer->setMaximumPrintLength(10);

        $data = '{"hello":"world"}';
        $this->printer->printIt($data, LogLevel::ERROR);

        $expected = '{
    "hel

(truncated)
';

        $this
            ->logger
            ->log(
                Argument::exact(LogLevel::ERROR),
                Argument::exact($expected)
            )
            ->shouldBeCalledTimes(1);
    }

    public function testDoesKnowItsContentType()
    {
        $this->assertSame('application/json', $this->printer->getContentType());
    }

}
