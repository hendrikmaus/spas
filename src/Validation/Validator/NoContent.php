<?php

namespace Hmaus\Spas\Validation\Validator;

use GuzzleHttp\Psr7\Response;
use Hmaus\Spas\Validation\ValidationError;
use Hmaus\Spas\Validation\Validator;
use Hmaus\SpasParser\ParsedRequest;

class NoContent implements Validator
{
    private $valid;

    public function validate(ParsedRequest $request, Response $response)
    {
        $isNoContentResponse = $response->getReasonPhrase() === 'No Content';

        if (!$isNoContentResponse) {
            $this->valid = true;

            return;
        }

        $this->valid = (
            !$request->getResponse()->getBody() && !$response->getBody()->getContents()
        );
    }

    public function isValid()
    {
        return $this->valid;
    }

    public function getId()
    {
        return 'no_content';
    }

    public function getName()
    {
        return 'No Content Validator';
    }

    public function getErrors()
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
}