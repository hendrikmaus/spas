<?php

namespace Hmaus\Spas\Validation\Validator;

use Hmaus\Spas\Validation\ValidationError;
use Hmaus\Spas\Validation\Validator;
use Hmaus\Spas\Parser\ParsedRequest;

class NoContent implements Validator
{
    private $valid = false;

    public function validate(ParsedRequest $request)
    {
        $response = $request->getActualResponse();
        $isNoContentResponse = $response->getReasonPhrase() === 'No Content';

        if (!$isNoContentResponse) {
            $this->valid = true;

            return;
        }

        $this->valid = (
            !$request->getExpectedResponse()->getBody() && !$response->getBody()
        );
    }

    public function isValid() : bool
    {
        return $this->valid;
    }

    public function getId() : string
    {
        return 'no_content';
    }

    public function getName() : string
    {
        return 'No Content Validator';
    }

    public function getErrors() : array
    {
        $errors = [];

        if (!$this->isValid()) {
            $error = new ValidationError();
            $error->message = 'Response is not empty';
            $error->property = 'messageBody';
            $errors[] = $error;
        }

        return $errors;
    }

    /**
     * Reset state of the validator
     *
     * E.g. clear all errors; reset `valid` property
     */
    public function reset()
    {
        $this->valid = false;
    }
}
