<?php

namespace Hmaus\Spas\Tests\Validation\Validator;

use GuzzleHttp\Psr7\Response;
use Hmaus\Spas\Validation\ValidationError;
use Hmaus\Spas\Validation\Validator\NoContent;
use Hmaus\Spas\Validation\Validator;
use Hmaus\SpasParser\ParsedRequest;
use Hmaus\SpasParser\ParsedResponse;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\StreamInterface;

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
        $this->validator = new NoContent();

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

    public function testNoContentResponsesAreValid()
    {
        $this
            ->response
            ->getReasonPhrase()
            ->willReturn('No Content')
            ->shouldBeCalledTimes(1);

        $this
            ->validator
            ->validate(
                $this->parsedRequest->reveal(),
                $this->response->reveal()
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
            ->response
            ->getReasonPhrase()
            ->willReturn('something')
            ->shouldBeCalledTimes(1);

        $this
            ->parsedResponse
            ->getBody()
            ->willReturn($parsedContent);

        $this
            ->responseBody
            ->getContents()
            ->willReturn($actualContent);

        $this
            ->validator
            ->validate(
                $this->parsedRequest->reveal(),
                $this->response->reveal()
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

}
