<?php

namespace Hmaus\Spas\Tests\Validation\Validator;

use GuzzleHttp\Psr7\Response;
use Hmaus\Spas\Validation\Validator;
use Hmaus\Spas\Validation\Validator\JsonSchema;
use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\Parser\ParsedResponse;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\StreamInterface;

class JsonSchemaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Validator|ObjectProphecy
     */
    private $validator;

    /**
     * @var ParsedRequest|ObjectProphecy
     */
    private $parsedRequest;

    /**
     * @var ParsedResponse|ObjectProphecy
     */
    private $actualResponse;

    /**
     * @var ParsedResponse|ObjectProphecy
     */
    private $parsedResponse;

    /**
     * @var StreamInterface|ObjectProphecy
     */
    private $responseBody;

    /**
     * @var \JsonSchema\Validator|ObjectProphecy
     */
    private $jsonSchemaValidator;

    protected function setUp()
    {
        $this->jsonSchemaValidator = $this->prophesize(\JsonSchema\Validator::class);

        $this->validator = new JsonSchema(
            $this->jsonSchemaValidator->reveal()
        );

        $this->parsedResponse = $this->prophesize(ParsedResponse::class);
        $this->parsedRequest = $this->prophesize(ParsedRequest::class);
        $this
            ->parsedRequest
            ->getExpectedResponse()
            ->willReturn(
                $this->parsedResponse->reveal()
            );

        $this->actualResponse = $this->prophesize(ParsedResponse::class);
    }

    public function testValidatesTrueIfThereIsNoSchema()
    {
        $this
            ->parsedResponse
            ->getSchema()
            ->willReturn(false)
            ->shouldBeCalledTimes(1);

        $this
            ->validator
            ->validate(
                $this->parsedRequest->reveal(),
                $this->actualResponse->reveal()
            );

        $this->assertTrue(
            $this->validator->isValid()
        );

        $this->assertEmpty($this->validator->getErrors());
    }

    public function testValidatesTrueIfSchemaIsFine()
    {
        $this
            ->parsedResponse
            ->getSchema()
            ->willReturn('{}')
            ->shouldBeCalledTimes(1);

        $this
            ->jsonSchemaValidator
            ->check(Argument::cetera())
            ->shouldBeCalledTimes(1);

        $this
            ->jsonSchemaValidator
            ->isValid()
            ->willReturn(true)
            ->shouldBeCalledTimes(1);

        $this
            ->actualResponse
            ->getBody()
            ->willReturn('{}');

        $this
            ->validator
            ->validate(
                $this->parsedRequest->reveal(),
                $this->actualResponse->reveal()
            );


        $this->assertTrue(
            $this->validator->isValid()
        );

        $this->assertEmpty($this->validator->getErrors());
    }

    public function testValidatesFalseIfSchemaDoesNotMatch()
    {
        $this
            ->parsedResponse
            ->getSchema()
            ->willReturn(true)
            ->shouldBeCalledTimes(1);

        $this
            ->actualResponse
            ->getBody()
            ->willReturn('{}');

        $this
            ->jsonSchemaValidator
            ->check(Argument::cetera())
            ->shouldBeCalledTimes(1);

        $this
            ->jsonSchemaValidator
            ->isValid()
            ->willReturn(false)
            ->shouldBeCalledTimes(1);

        $this
            ->jsonSchemaValidator
            ->getErrors()
            ->willReturn([
                ['message' => 'msg1', 'property' => 'prop1'],
                ['message' => 'msg2', 'property' => 'prop2'],
            ])
            ->shouldBeCalledTimes(1);

        $this
            ->validator
            ->validate(
                $this->parsedRequest->reveal(),
                $this->actualResponse->reveal()
            );

        $this->assertFalse(
            $this->validator->isValid()
        );

        $this->assertNotEmpty($this->validator->getErrors());
    }

    public function testValidatesFalseIfSchemaIsPresentButBodyIsNot()
    {
        $this
            ->parsedResponse
            ->getSchema()
            ->willReturn('{"some":"schema"}')
            ->shouldBeCalledTimes(1);

        $this
            ->jsonSchemaValidator
            ->check(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->jsonSchemaValidator
            ->isValid()
            ->willReturn(true)
            ->shouldNotBeCalled();

        $this
            ->actualResponse
            ->getBody()
            ->willReturn(null);

        $this
            ->validator
            ->validate(
                $this->parsedRequest->reveal(),
                $this->actualResponse->reveal()
            );

        $this->assertFalse(
            $this->validator->isValid()
        );

        $this->assertNotEmpty($this->validator->getErrors());
    }

    public function testItCanHasId()
    {
        $this->assertNotEmpty($this->validator->getId());
    }

    public function testItCanSayItsName()
    {
        $this->assertNotEmpty($this->validator->getName());
    }

    public function testItCanResetItself()
    {
        $this->validator->reset();

        $this->assertEmpty($this->validator->getErrors());
        $this->assertFalse($this->validator->isValid());
    }
}
