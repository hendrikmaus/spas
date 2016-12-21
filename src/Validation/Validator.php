<?php

namespace Hmaus\Spas\Validation;

use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\Parser\ParsedResponse;

interface Validator
{
    /**
     * Validate given request and response
     *
     * @param ParsedRequest $request
     * @param ParsedResponse $response
     * @return mixed
     */
    public function validate(ParsedRequest $request, ParsedResponse $response);

    /**
     * Whether or not the validation result is valid
     *
     * @return bool
     */
    public function isValid() : bool;

    /**
     * Get ID of the parser, e.g. `json_schema`
     *
     * @return string
     */
    public function getId() : string;

    /**
     * Human readable validator name, to end on "Validator"
     *
     * E.g. `JSON Schema Validator`; is used to build a sentence for console output
     * @return string
     */
    public function getName() : string;

    /**
     * Return an array of errors
     *
     * @return ValidationError[]
     */
    public function getErrors() : array;

    /**
     * Reset state of the validator
     *
     * E.g. clear all errors; reset `valid` property
     */
    public function reset();
}
