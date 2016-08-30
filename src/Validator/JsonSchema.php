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
    private $valid;

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
    }

    public function isValid()
    {
        return $this->valid;
    }

    public function getName()
    {
        return 'json_schema';
    }
}