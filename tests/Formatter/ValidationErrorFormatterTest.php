<?php

namespace Hmaus\Spas\Tests\Formatter;

use Hmaus\Spas\Formatter\ValidationErrorFormatter;
use Hmaus\Spas\Validation\ValidationError;
use Hmaus\Spas\Validation\Validator\JsonSchema;
use Hmaus\Spas\Validation\Validator\TextPlain;
use JsonSchema\Validator;

class ValidationErrorFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ValidationErrorFormatter
     */
    private $formatter;

    protected function setUp()
    {
        $this->formatter = new ValidationErrorFormatter();
    }

    public function testDoesKnowItsContentType()
    {
        $this->assertNotEmpty($this->formatter->getContentTypes());
    }

    public function testDoesNotPrintAnEmptyReport()
    {
        $result = $this
            ->formatter
            ->format([]);

        $this->assertEmpty($result);
    }

    public function testDoesNotPrintAllValidReport()
    {
        $report = [
            new Validator(),
            new Validator()
        ];

        $this
            ->formatter
            ->format($report);
    }

    public function testPrintsProperErrorReport()
    {
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

        $result = $this
            ->formatter
            ->format($report);

        $expected = 'Plain Text Validator failed with:
[error]   Property: messageBody
[error]   Message : I am error one
[error] 
[error] Json Schema Validator failed with:
[error]   Property: property_one
[error]   Message : I am error one
[error] 
[error]   Property: property_two
[error]   Message : I am error two';

        $this->assertSame($expected, $result);
    }
}
