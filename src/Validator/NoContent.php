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

class NoContent implements Validator
{
    private $valid;

    public function validate(ParsedRequest $request, Response $response)
    {
        $isNoContentResponse = $response->getReasonPhrase() === 'No Content';
        $expectedResponseBodyEmpty = !$request->getResponse()->getBody();
        $actualResponseBodyEmpty = !$response->getBody()->getContents();

        if (!$isNoContentResponse) {
            $this->valid = true;
            return;
        }

        $this->valid = $expectedResponseBodyEmpty && $actualResponseBodyEmpty;
    }

    public function isValid()
    {
        return $this->valid;
    }

    public function getName()
    {
        return 'no_content';
    }
}