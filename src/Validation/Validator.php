<?php

namespace Hmaus\Spas\Validation;

use GuzzleHttp\Psr7\Response;
use Hmaus\SpasParser\ParsedRequest;

interface Validator
{
    /**
     * Validate given request and response
     *
     * @param ParsedRequest $request
     * @param Response $response
     * @return mixed
     */
    public function validate(ParsedRequest $request, Response $response);

    /**
     * Whether or not the validation result is valid
     *
     * @return bool
     */
    public function isValid();

    /**
     * Get ID of the parser, e.g. `json_schema`
     *
     * @return string
     */
    public function getId();

    /**
     * Human readbale validator name, to end on "Validator"
     *
     * E.g. `JSON Schema Validator`; is used to build a sentence for console output
     * @return string
     */
    public function getName();

    /**
     * Rurn an array of errors
     *
     * @return ValidationError[]
     */
    public function getErrors();
}