<?php

namespace Hmaus\Spas\Tests\Validation\Validator;

use GuzzleHttp\Psr7\Response;
use Hmaus\Spas\Validation\ValidationError;
use Hmaus\Spas\Validation\Validator;
use Hmaus\Spas\Validation\Validator\TextPlain;
use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\Parser\ParsedResponse;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\HeaderBag;

class TextPlainTest extends \PHPUnit_Framework_TestCase
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
     * @var Response|ObjectProphecy
     */
    private $response;

    /**
     * @var ParsedResponse|ObjectProphecy
     */
    private $parsedResponse;

    /**
     * @var StreamInterface|ObjectProphecy
     */
    private $responseBody;

    protected function setUp()
    {
        $this->validator = new TextPlain();

        $this->parsedResponse = $this->prophesize(ParsedResponse::class);

        $this->parsedRequest = $this->prophesize(ParsedRequest::class);
        $this
            ->parsedRequest
            ->getResponse()
            ->willReturn(
                $this->parsedResponse->reveal()
            );

        $this->responseBody = $this->prophesize(StreamInterface::class);

        $this->response = $this->prophesize(Response::class);
        $this
            ->response
            ->getBody()
            ->willReturn(
                $this->responseBody->reveal()
            );
    }

    public function testValidatesTrueWithoutContentTypeHeader()
    {
        $this
            ->parsedResponse
            ->getHeaders()
            ->willReturn(new HeaderBag())
            ->shouldBeCalledTimes(1);

        $this
            ->validator
            ->validate(
                $this->parsedRequest->reveal(),
                $this->response->reveal()
            );

        $this->assertTrue(
            $this->validator->isValid()
        );

        $errors = $this->validator->getErrors();
        $this->assertEmpty($errors);
    }

    public function testValidatesTrueWithContentTypeHeaderIsNotTextPlain()
    {
        $headerBag = new HeaderBag();
        $headerBag->set('content-type', 'application/json');

        $this
            ->parsedResponse
            ->getHeaders()
            ->willReturn($headerBag)
            ->shouldBeCalledTimes(2);

        $this
            ->validator
            ->validate(
                $this->parsedRequest->reveal(),
                $this->response->reveal()
            );

        $this->assertTrue(
            $this->validator->isValid()
        );

        $errors = $this->validator->getErrors();
        $this->assertEmpty($errors);
    }

    public function testValidatesTrueWhenBothTextPlainBodiesAreIdentical()
    {
        $headerBag = new HeaderBag();
        $headerBag->set('content-type', 'text/plain');

        $this
            ->parsedResponse
            ->getHeaders()
            ->willReturn($headerBag)
            ->shouldBeCalledTimes(2);

        $this
            ->parsedResponse
            ->getBody()
            ->willReturn('hello')
            ->shouldBeCalledTimes(1);

        $this
            ->responseBody
            ->getContents()
            ->willReturn('hello')
            ->shouldBeCalledTimes(1);

        $this
            ->validator
            ->validate(
                $this->parsedRequest->reveal(),
                $this->response->reveal()
            );

        $this->assertTrue(
            $this->validator->isValid()
        );

        $errors = $this->validator->getErrors();
        $this->assertEmpty($errors);
    }

    public function testValidatesFalseForDifferentBodies()
    {
        $headerBag = new HeaderBag();
        $headerBag->set('content-type', 'text/plain');

        $this
            ->parsedResponse
            ->getHeaders()
            ->willReturn($headerBag)
            ->shouldBeCalledTimes(2);

        $this
            ->parsedResponse
            ->getBody()
            ->willReturn('hello world')
            ->shouldBeCalledTimes(2);

        $this
            ->responseBody
            ->getContents()
            ->willReturn('hello!')
            ->shouldBeCalledTimes(2);

        $this
            ->validator
            ->validate(
                $this->parsedRequest->reveal(),
                $this->response->reveal()
            );

        $this->assertFalse(
            $this->validator->isValid()
        );

        $errors = $this->validator->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertCount(1, $errors);

        /** @var ValidationError $error */
        $error = array_shift($errors);
        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertNotEmpty($error->message);
        $this->assertNotEmpty($error->property);

        $this->assertContains('-hello!', $error->message);
        $this->assertContains('+hello world', $error->message);
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
