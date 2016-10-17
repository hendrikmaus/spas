<?php

namespace Hmaus\Spas\Tests\Request\Result\Printer;

use Hmaus\Spas\Request\Result\Printer\ValidationReportPrinter;
use JsonSchema\Validator;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ValidationReportPrinterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $logger;

    /**
     * @var ValidationReportPrinter
     */
    private $printer;

    protected function setUp()
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->printer = new ValidationReportPrinter($this->logger->reveal());
    }

    public function testDoesNotPrintAnEmptyReport()
    {
        $this
            ->logger
            ->log(Argument::type('string'), Argument::type('int'), Argument::type('array'))
            ->shouldNotBeCalled();

        $this
            ->printer
            ->print([], LogLevel::ERROR);
    }

    public function testDoesNotPrintAllValidReport()
    {
        $this
            ->logger
            ->log(Argument::type('string'), Argument::type('int'), Argument::type('array'))
            ->shouldNotBeCalled();

        $report = [
            new Validator(),
            new Validator()
        ];

        $this
            ->printer
            ->print($report, LogLevel::ERROR);
    }

}
