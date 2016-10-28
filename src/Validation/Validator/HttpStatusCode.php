<?php

namespace Hmaus\Spas\Validation\Validator;

use GuzzleHttp\Psr7\Response;
use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\Validation\ValidationError;
use Hmaus\Spas\Validation\Validator;

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

    public function validate(ParsedRequest $request, Response $response)
    {
        $expected = $request->getResponse()->getStatusCode();
        $actual   = $response->getStatusCode();

        $this->isValid = $expected === $actual;

        if (!$this->isValid()) {
            $error = new ValidationError();
            $error->property = 'HTTP Status Code';
            $error->message  = sprintf('Expected %d does not match actual %d', $expected, $actual);
            $this->errors[]  = $error;
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
