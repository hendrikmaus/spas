<?php

namespace Hmaus\Spas\Validation\Validator;

use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\Validation\ValidationError;
use Hmaus\Spas\Validation\Validator;
use Psr\Log\LoggerInterface;

class HttpStatusCode implements Validator
{
    /**
     * @var bool
     */
    private $isValid = false;

    /**
     * @var ValidationError[]
     */
    private $errors = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function validate(ParsedRequest $request)
    {
        $expected = $request->getExpectedResponse()->getStatusCode();
        $response = $request->getActualResponse();
        $actual   = $response->getStatusCode();

        $this->expectedVsActual($expected, $actual);

        if (!$this->isValid()) {
            $error = new ValidationError();
            $error->property = 'HTTP Status Code';
            $error->message  = sprintf('Expected %d does not match actual %d', $expected, $actual);
            $this->errors[]  = $error;
        }
    }

    private function expectedVsActual(int $expected, int $actual)
    {
        $this->isValid = $expected === $actual;

        if ($expected === 202 && $actual === 200) {
            $this->logger->info('Expected HTTP 202; actual HTTP 200 -> valid edge case');
            $this->isValid = true;
        }
    }

    public function isValid() : bool
    {
        return $this->isValid;
    }

    public function getId() : string
    {
        return 'http_status_code';
    }

    public function getName() : string
    {
        return 'HTTP Status Code Validator';
    }

    public function getErrors() : array
    {
        return $this->errors;
    }

    public function reset()
    {
        $this->isValid = false;
        $this->errors = [];
    }
}
