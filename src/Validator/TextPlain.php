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

class TextPlain implements Validator
{
    private $valid;

    public function validate(ParsedRequest $request, Response $response)
    {
        $hasContentTypeHeader = $request->getResponse()->headers->has('content-type');

        if (!$hasContentTypeHeader) {
            $this->valid = true;
            return;
        }

        $isTextPlain = $request->getResponse()->headers->get('content-type') === 'text/plain';

        if ($isTextPlain) {
            $this->valid = $response->getBody()->getContents() === $request->getResponse()->getBody();
        }

        $this->valid = true;
    }

    public function isValid()
    {
        return $this->valid;
    }

    public function getName()
    {
        return 'text/plain';
    }
}