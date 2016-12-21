<?php

namespace Hmaus\Spas\Validation\Validator;

use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\Parser\ParsedResponse;
use Hmaus\Spas\Validation\ValidationError;
use Hmaus\Spas\Validation\Validator;
use Symfony\Component\HttpFoundation\HeaderBag;

class Header implements Validator
{
    /**
     * @var bool
     */
    private $isValid = false;

    /**
     * @var ValidationError[]
     */
    private $errors = [];

    public function validate(ParsedRequest $request, ParsedResponse $response)
    {
        $expected = $request->getExpectedResponse()->getHeaders();
        $actual   = $response->getHeaders();

        foreach ($expected as $header => $value) {
            if ($actual->has($header)) {
                continue;
            }

            if ($header === 'retry-after' && $response->getStatusCode() === 200) {
                continue;
            }

            $this->addError(
                'Header Missing',
                sprintf('Expected "%s", but not found', $header)
            );
        }

        $this->isValid = count($this->getErrors()) === 0;
    }

    public function isValid() : bool
    {
        return $this->isValid;
    }

    public function getId() : string
    {
        return 'header';
    }

    public function getName() : string
    {
        return 'Header Validator';
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

    private function addError(string $property, string $message)
    {
        $error = new ValidationError();
        $error->property = $property;
        $error->message = $message;
        $this->errors[] = $error;
    }
}
