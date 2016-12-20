<?php

namespace Hmaus\Spas\Tests\Validation\Validator;

use Hmaus\Spas\Validation\ValidationError;
use Hmaus\Spas\Validation\Validator\NoContent;
use Hmaus\Spas\Validation\Validator;
use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\Parser\ParsedResponse;
use Prophecy\Prophecy\ObjectProphecy;

class NoContentTest extends \PHPUnit_Framework_TestCase
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

    protected function setUp()
    {
        $this->validator = new NoContent();

        $this->actualResponse = $this->prophesize(ParsedResponse::class);
        $this->parsedResponse = $this->prophesize(ParsedResponse::class);

        $this->parsedRequest = $this->prophesize(ParsedRequest::class);
        $this
            ->parsedRequest
            ->getExpectedResponse()
            ->willReturn(
                $this->parsedResponse->reveal()
            );

        $this
            ->parsedRequest
            ->getActualResponse()
            ->willReturn(
                $this->actualResponse->reveal()
            );
    }

    public function testNoContentResponsesAreValid()
    {
        $this
            ->actualResponse
            ->getReasonPhrase()
            ->willReturn('No Content')
            ->shouldBeCalledTimes(1);

        $this
            ->actualResponse
            ->getBody()
            ->willReturn('')
            ->shouldBeCalledTimes(1);

        $this
            ->validator
            ->validate(
                $this->parsedRequest->reveal()
            );

        $this->assertTrue($this->validator->isValid());
        $this->assertEmpty($this->validator->getErrors());
    }

    public function testNoNoContentResponsesAreValid()
    {
        $this
            ->actualResponse
            ->getReasonPhrase()
            ->willReturn('I have content')
            ->shouldBeCalledTimes(1);

        $this
            ->validator
            ->validate(
                $this->parsedRequest->reveal()
            );

        $this->assertTrue($this->validator->isValid());
        $this->assertEmpty($this->validator->getErrors());
    }

    public function contentDataProvider()
    {
        return [
            // parsed content, actual content, result
            ['', 'not empty', false],
            ['not empty', '', false],
            ['not empty', 'not empty', false],
            ['', '', true],
        ];
    }

    /**
     * @dataProvider contentDataProvider
     */
    public function testResultScenarios($parsedContent, $actualContent, $result)
    {
        $this
            ->actualResponse
            ->getReasonPhrase()
            ->willReturn('No Content')
            ->shouldBeCalledTimes(1);

        $this
            ->parsedResponse
            ->getBody()
            ->willReturn($parsedContent);

        $this
            ->actualResponse
            ->getBody()
            ->willReturn($actualContent);

        $this
            ->validator
            ->validate(
                $this->parsedRequest->reveal()
            );

        $this->assertSame($result, $this->validator->isValid());

        if ($result === false) {
            $errors = $this->validator->getErrors();
            /** @var ValidationError $error */
            $error = array_shift($errors);

            $this->assertInstanceOf(ValidationError::class, $error);
            $this->assertNotEmpty($error->message);
            $this->assertNotEmpty($error->property);
        }
        else {
            $this->assertEmpty($this->validator->getErrors());
        }
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

        $this->assertFalse($this->validator->isValid());
    }

}
