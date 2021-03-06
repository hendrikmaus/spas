<?php

namespace Hmaus\Spas\Tests\Validation;

use Hmaus\Spas\Parser\ParsedResponse;
use Hmaus\Spas\Validation\Validator;
use Hmaus\Spas\Validation\ValidatorService;
use Hmaus\Spas\Parser\ParsedRequest;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class ValidatorServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $logger;

    /**
     * @var ValidatorService
     */
    private $validatorService;

    /**
     * @var ParsedRequest|ObjectProphecy
     */
    private $parsedRequest;

    /**
     * @var ParsedResponse|ObjectProphecy
     */
    private $parsedResponse;

    protected function setUp()
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->validatorService = new ValidatorService(
            $this->logger->reveal()
        );

        $this->parsedResponse = $this->prophesize(ParsedResponse::class);
        $this->parsedRequest  = $this->prophesize(ParsedRequest::class);

        $this
            ->parsedRequest
            ->getActualResponse()
            ->willReturn($this->parsedResponse->reveal());
    }

    public function testCanAddValidators()
    {
        $validator = $this->prophesize(Validator::class);

        $this
            ->validatorService
            ->addValidator($validator->reveal());

        $validators = $this->validatorService->getValidators();

        $this->assertNotEmpty($validators);

        $this->assertInstanceOf(Validator::class, $validators[0]);
    }

    public function testReportStartsOutEmpty()
    {
        $this->assertEmpty($this->validatorService->getReport());
    }

    public function testReportCanBeResetted()
    {
        $this->validatorService->reset();
        $this->assertEmpty($this->validatorService->getReport());
    }

    public function testReportsTrueIfNothingIsInTheReport()
    {
        $this->assertTrue(
            $this->validatorService->isValid()
        );
    }

    public function testReportsFalseIfValidatorFails()
    {
        $validator = $this->prophesize(Validator::class);
        $validator
            ->validate(Argument::cetera())
            ->shouldBecalledTimes(1);

        $validator
            ->getId()
            ->willReturn('test')
            ->shouldbeCalledTimes(1);

        $validator
            ->isValid()
            ->willReturn(false)
            ->shouldbeCalledTimes(1);

        $this
            ->validatorService
            ->addValidator($validator->reveal());

        $this
            ->validatorService
            ->validate(
                $this->parsedRequest->reveal()
            );

        $this->assertFalse(
            $this->validatorService->isValid()
        );
    }

    public function testReportsTrueIfValidatorsAreFine()
    {
        $validator = $this->prophesize(Validator::class);
        $validator
            ->validate(Argument::cetera())
            ->shouldBecalledTimes(1);

        $validator
            ->getId()
            ->willReturn('test')
            ->shouldbeCalledTimes(1);

        $validator
            ->isValid()
            ->willReturn(true)
            ->shouldbeCalledTimes(1);

        $this
            ->validatorService
            ->addValidator($validator->reveal());

        $this
            ->validatorService
            ->validate(
                $this->parsedRequest->reveal()
            );

        $this->assertTrue(
            $this->validatorService->isValid()
        );
    }

    public function testCanResetItselfAndAllValidators()
    {
        $validator = $this->prophesize(Validator::class);

        $validator
            ->reset()
            ->shouldBeCalledTimes(1);

        $this
            ->validatorService
            ->addValidator($validator->reveal());

        $this
            ->validatorService
            ->reset();
    }

    public function testKnowsItsContentType()
    {
        $this->assertNotEmpty($this->validatorService->getContentType());
    }

}
