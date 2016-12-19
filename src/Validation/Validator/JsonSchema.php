<?php

namespace Hmaus\Spas\Validation\Validator;

use Hmaus\Spas\Validation\ValidationError;
use Hmaus\Spas\Validation\Validator;
use Hmaus\Spas\Parser\ParsedRequest;
use JsonSchema\Validator as JsonSchemaValidator;

class JsonSchema implements Validator
{
    /**
     * @var bool
     */
    private $valid = false;

    /**
     * @var ValidationError[]
     */
    private $errors = [];

    /**
     * @var JsonSchemaValidator
     */
    private $jsonSchemaValidator;

    public function __construct(JsonSchemaValidator $jsonSchemaValidator)
    {
        $this->jsonSchemaValidator = $jsonSchemaValidator;
    }

    public function validate(ParsedRequest $request)
    {
        $schema   = $request->getExpectedResponse()->getSchema();
        $response = $request->getActualResponse();

        if (empty($schema)) {
            $this->valid = true;
            return;
        }

        $decodedBody = json_decode($response->getBody());
        $decodedSchema = json_decode($schema);

        if (null === $decodedBody) {
            $this->failEmptyBody();
            return;
        }

        $this->jsonSchemaValidator->check(
            $decodedBody,
            $decodedSchema
        );

        $this->valid = $this->jsonSchemaValidator->isValid();

        if ($this->valid) {
            return;
        }

        foreach ($this->jsonSchemaValidator->getErrors() as $schemaError) {
            $error = new ValidationError();
            $error->message = $schemaError['message'];
            $error->property = $schemaError['property'];
            $this->errors[] = $error;
        }
    }

    public function isValid() : bool
    {
        return $this->valid;
    }

    public function getId() : string
    {
        return 'json_schema';
    }

    public function getName() : string
    {
        return 'JSON Schema Validator';
    }

    public function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * Set the validator state to invalid and add empty body error
     */
    private function failEmptyBody()
    {
        $this->valid = false;

        $error = new ValidationError();
        $error->property = 'root';
        $error->message = 'Expected a body according to given schema, but no body found';
        $this->errors[] = $error;
    }

    /**
     * Reset state of the validator
     *
     * E.g. clear all errors; reset `valid` property
     */
    public function reset()
    {
        $this->errors = [];
        $this->valid  = false;
        $this->jsonSchemaValidator->reset();
    }
}
