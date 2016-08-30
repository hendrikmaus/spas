<?php

namespace Hmaus\Spas\Validator;

use GuzzleHttp\Psr7\Response;
use Hmaus\SpasParser\ParsedRequest;

interface Validator
{
    public function validate(ParsedRequest $request, Response $response);
    public function isValid();
    public function getName();
}