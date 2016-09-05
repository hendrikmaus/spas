<?php

namespace Hmaus\Spas\Validation\Validator;

use GuzzleHttp\Psr7\Response;
use Hmaus\Spas\Validation\ValidationError;
use Hmaus\Spas\Validation\Validator;
use Hmaus\SpasParser\ParsedRequest;
use JsonSchema\Validator as JsonSchemaValidator;

class JsonSchema implements Validator
{
    /**
     * @var bool
     */
    private $valid;

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

    public function validate(ParsedRequest $request, Response $response)
    {
        $hasSchema = $request->getResponse()->getSchema();

        if (!$hasSchema) {
            $this->valid = true;
            return;
        }

        $this->jsonSchemaValidator->check(
            json_decode($response->getBody()->getContents()),
            json_decode($request->getResponse()->getSchema())
        );

        $this->valid = $this->jsonSchemaValidator->isValid();

        if (!$this->valid) {
            foreach ($this->jsonSchemaValidator->getErrors() as $schemaError) {
                $error = new ValidationError();
                $error->message = $schemaError['message'];
                $error->property = $schemaError['property'];
                $this->errors[] = $error;
            }
        }
    }

    public function isValid()
    {
        return $this->valid;
    }

    public function getId()
    {
        return 'json_schema';
    }

    public function getName()
    {
        return 'JSON Schema Validator';
    }

    public function getErrors()
    {
        return $this->errors;
    }
}