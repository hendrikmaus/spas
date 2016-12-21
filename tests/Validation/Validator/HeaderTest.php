<?php

namespace Hmaus\Spas\Tests\Validation\Validator;

use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\Parser\ParsedResponse;
use Hmaus\Spas\Validation\ValidationError;
use Hmaus\Spas\Validation\Validator\Header;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\HeaderBag;

class HeaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Header|ObjectProphecy
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
     * @var ParsedResponse|ObjectProphecy
     */
    private $actualResponse;

    /**
     * @var HeaderBag
     */
    private $headerBag;

    protected function setUp()
    {
        $this->validator = new Header();

        $this->headerBag = new HeaderBag();

        $this->parsedResponse = $this->prophesize(ParsedResponse::class);

        $this
            ->parsedResponse
            ->getHeaders()
            ->willReturn(
                $this->headerBag
            );

        $this->parsedRequest = $this->prophesize(ParsedRequest::class);
        $this
            ->parsedRequest
            ->getExpectedResponse()
            ->willReturn(
                $this->parsedResponse->reveal()
            );

        $this->actualResponse = $this->prophesize(ParsedResponse::class);
        $this
            ->actualResponse
            ->getStatusCode()
            ->willReturn(200);

        $this
            ->parsedRequest
            ->getActualResponse()
            ->willReturn(
                $this->actualResponse->reveal()
        );
    }

    private function runValidatior()
    {
        $this->validator->validate(
            $this->parsedRequest->reveal(),
            $this->actualResponse->reveal()
        );
    }

    public function headerTestDataProvider()
    {
        /*
         * [
         *     expected,
         *     actual,
         *     result,
         *     errors
         * ]
         *
         * "errors" is an assoc array of the error `property` as key
         * and the count of it you expect for a value
         */
        return [
            [ [], [], [], true ],
            [
                [
                    'User-Agent' => 'mozilla',
                    'X-Vnd-Test' => '4711'
                ],
                [
                    'User-Agent' => 'mozilla'
                ],
                [
                    'Header Missing' => 1
                ],
                false
            ],
            [
                [
                    'User-Agent' => 'mozilla',
                    'X-Vnd-Test' => '4711'
                ],
                [
                    'User-Agent' => 'mozilla',
                    'ETag' => '253f8o7'
                ],
                [
                    'Header Missing' => 1
                ],
                false
            ],
            [
                [
                    'User-Agent' => 'mozilla',
                    'X-Vnd-Test' => '4711'
                ],
                [
                    'User-Agent' => '42',
                    'X-Vnd-Test' => '42',
                    'ETag' => '253f8o7',
                    'Cache-Control' => 'maxage 3600'
                ],
                [
                    'Header Missing' => 1
                ],
                true
            ],
            [
                [
                    'User-Agent' => 'mozilla',
                    'X-Vnd-Test' => '4711',
                    'Retry-After' => 1 // important to test an edge case
                ],
                [
                    'User-Agent' => '42',
                    'X-Vnd-Test' => '42',
                    'ETag' => '253f8o7',
                    'Cache-Control' => 'maxage 3600',
                ],
                [
                    'Header Missing' => 1
                ],
                true
            ],
        ];
    }

    /**
     * @dataProvider headerTestDataProvider
     * @param array $expected
     * @param array $actual
     * @param array $errors
     * @param bool $result
     */
    public function testHeaderScenarios(array $expected, array $actual, array $errors, bool $result)
    {
        $this
            ->headerBag
            ->add($expected);

        $this
            ->actualResponse
            ->getHeaders()
            ->willReturn(new HeaderBag($actual));

        $this->runValidatior();
        $this->assertSame($result, $this->validator->isValid());

        if ($this->validator->isValid() === false) {
            $actualErrors = $this->validator->getErrors();
            $this->assertNotEmpty($actualErrors);

            $errorCounter = [];
            /** @var ValidationError $actualError */
            foreach ($actualErrors as $actualError) {
                if (!isset($errorCounter[$actualError->property])) {
                    $errorCounter[$actualError->property] = 1;
                    continue;
                }

                $errorCounter[$actualError->property] += 1;
            }

            foreach ($errors as $expectedError => $count) {
                if (!isset($errorCounter[$expectedError])) {
                    $this->fail(
                        sprintf('Error "%s" was not thrown in the validator', $expectedError)
                    );
                }
                $this->assertSame(
                    $count,
                    $errorCounter[$expectedError],
                    sprintf('Error count for "%s" expected %d, found %d', $expectedError, $count, $errorCounter[$expectedError])
                );
            }
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
        $this->assertEmpty($this->validator->getErrors());
    }
}
