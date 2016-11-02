<?php

namespace Hmaus\Spas\Tests\Validation\Validator;

use GuzzleHttp\Psr7\Response;
use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\Parser\ParsedResponse;
use Hmaus\Spas\Validation\ValidationError;
use Hmaus\Spas\Validation\Validator\HttpStatusCode;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class HttpStatusCodeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HttpStatusCode|ObjectProphecy
     */
    private $validator;

    /**
     * @var ParsedResponse|ObjectProphecy
     */
    private $parsedResponse;

    /**
     * @var ParsedRequest|ObjectProphecy
     */
    private $parsedRequest;

    /**
     * @var Response|ObjectProphecy
     */
    private $response;

    protected function setUp()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $this->validator = new HttpStatusCode($logger->reveal());

        $this->parsedResponse = $this->prophesize(ParsedResponse::class);
        $this->parsedRequest = $this->prophesize(ParsedRequest::class);
        $this
            ->parsedRequest
            ->getResponse()
            ->willReturn(
                $this->parsedResponse->reveal()
            );

        $this->response = $this->prophesize(Response::class);
    }

    public function testValidatesFalseIfStatusCodesDoNotMatch()
    {
        $this
            ->response
            ->getStatusCode()
            ->willReturn(200);

        $this
            ->parsedResponse
            ->getStatusCode()
            ->willReturn(201);

        $this->validator->validate($this->parsedRequest->reveal(), $this->response->reveal());
        $this->assertFalse($this->validator->isValid());

        $this->assertNotEmpty($this->validator->getErrors());
        $this->assertCount(1, $this->validator->getErrors());

        $errors = $this->validator->getErrors();
        $error = array_pop($errors);

        $this->assertInstanceOf(ValidationError::class, $error);
    }

    public function testValidatesTrueIfStatusCodesDoMatch()
    {
        $this
            ->response
            ->getStatusCode()
            ->willReturn(200);

        $this
            ->parsedResponse
            ->getStatusCode()
            ->willReturn(200);

        $this->validator->validate($this->parsedRequest->reveal(), $this->response->reveal());
        $this->assertTrue($this->validator->isValid());
        $this->assertEmpty($this->validator->getErrors());
    }

    public function testValidatesTrueIfStatusCode202Returns200()
    {
        $this
            ->response
            ->getStatusCode()
            ->willReturn(200);

        $this
            ->parsedResponse
            ->getStatusCode()
            ->willReturn(202);

        $this->validator->validate($this->parsedRequest->reveal(), $this->response->reveal());
        $this->assertTrue($this->validator->isValid());
        $this->assertEmpty($this->validator->getErrors());
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
        $this->assertEmpty($this->validator->getErrors());
    }
}