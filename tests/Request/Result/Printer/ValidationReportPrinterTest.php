<?php

namespace Hmaus\Spas\Tests\Request\Result\Printer;

use Hmaus\Spas\Request\Result\Printer\ValidationReportPrinter;
use Hmaus\Spas\Validation\ValidationError;
use Hmaus\Spas\Validation\Validator\JsonSchema;
use Hmaus\Spas\Validation\Validator\TextPlain;
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
            ->log(Argument::type('string'), Argument::type('string'), Argument::type('array'))
            ->shouldNotBeCalled();

        $this
            ->printer
            ->printIt([], LogLevel::ERROR);
    }

    public function testDoesNotPrintAllValidReport()
    {
        $this
            ->logger
            ->log(Argument::type('string'), Argument::type('string'), Argument::type('array'))
            ->shouldNotBeCalled();

        $report = [
            new Validator(),
            new Validator()
        ];

        $this
            ->printer
            ->printIt($report, LogLevel::ERROR);
    }

    public function testPrintsProperErrorReport()
    {
        $this
            ->logger
            ->log(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalled();

        $textPlainErrorOne = new ValidationError();
        $textPlainErrorOne->property = 'messageBody';
        $textPlainErrorOne->message = 'I am error one';

        $validationTextPlain = $this->prophesize(TextPlain::class);
        $validationTextPlain
            ->isValid()
            ->willReturn(false);
        $validationTextPlain
            ->getName()
            ->willReturn('Plain Text Validator');
        $validationTextPlain
            ->getErrors()
            ->willReturn([$textPlainErrorOne]);

        $jsonSchemaErrorOne = new ValidationError();
        $jsonSchemaErrorOne->property = 'property_one';
        $jsonSchemaErrorOne->message = 'I am error one';

        $jsonSchemaErrorTwo = new ValidationError();
        $jsonSchemaErrorTwo->property = 'property_two';
        $jsonSchemaErrorTwo->message = 'I am error two';

        $validationJsonSchema = $this->prophesize(JsonSchema::class);
        $validationJsonSchema
            ->isValid()
            ->willReturn(false);
        $validationJsonSchema
            ->getName()
            ->willReturn('Json Schema Validator');
        $validationJsonSchema
            ->getErrors()
            ->willReturn([$jsonSchemaErrorOne, $jsonSchemaErrorTwo]);

        $report = [
            $validationTextPlain->reveal(),
            $validationJsonSchema->reveal()
        ];

        $this
            ->printer
            ->printIt($report, LogLevel::ERROR);
    }

    public function testDoesKnowItsContentType()
    {
        $this->assertSame('application/vnd.hmaus.spas.validation_report', $this->printer->getContentType());
    }

}
