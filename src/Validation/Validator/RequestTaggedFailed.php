<?php

namespace Hmaus\Spas\Validation\Validator;

use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\Validation\ValidationError;
use Hmaus\Spas\Validation\Validator;

class RequestTaggedFailed implements Validator
{
    /**
     * @var bool
     */
    private $isValid = false;

    /**
     * @var ValidationError[]
     */
    private $errors = [];

    public function validate(ParsedRequest $request)
    {
        $this->isValid = !$request->hasFailed();

        if (!$this->isValid()) {
            $this->addError("Custom error message", $request->getCustomErrorMessage());
        }
    }

    public function isValid() : bool
    {
        return $this->isValid;
    }

    public function getId() : string
    {
        return 'request_tagged_failed';
    }

    public function getName() : string
    {
        return 'Request';
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
