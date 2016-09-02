<?php
/**
 * @author    Hendrik Maus <aidentailor@gmail.com>
 * @since     2016-08-14
 * @copyright 2016 (c) Hendrik Maus
 * @license   All rights reserved.
 * @package   spas
 */

namespace Hmaus\Spas\Validator;

use GuzzleHttp\Psr7\Response;
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

    public function validate(ParsedRequest $request, Response $response)
    {
        $hasSchema = $request->getResponse()->getSchema();

        if (!$hasSchema) {
            $this->valid = true;
            return;
        }

        $schemaValidator = new JsonSchemaValidator();

        // todo json checking ftw
        $schemaValidator->check(
            json_decode($response->getBody()->getContents()),
            json_decode($request->getResponse()->getSchema())
        );

        $this->valid = $schemaValidator->isValid();

        if (!$this->valid) {
            foreach ($schemaValidator->getErrors() as $schemaError) {
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