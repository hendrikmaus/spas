<?php

namespace Hmaus\Spas\Validation;

use Hmaus\Spas\Parser\ParsedRequest;
use Psr\Http\Message\ResponseInterface;

interface Validator
{
    /**
     * Validate given request and response
     *
     * @param ParsedRequest $request
     * @param ResponseInterface $response
     * @return mixed
     */
    public function validate(ParsedRequest $request, ResponseInterface $response);

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
     * Human readbale validator name, to end on "Validator"
     *
     * E.g. `JSON Schema Validator`; is used to build a sentence for console output
     * @return string
     */
    public function getName() : string;

    /**
     * Rurn an array of errors
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
