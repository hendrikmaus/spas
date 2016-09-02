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
use SebastianBergmann\Diff\Differ;

class TextPlain implements Validator
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
     * @var Differ
     */
    private $differ;

    public function __construct()
    {
        $this->differ = new Differ("\n--- Original\n+++ New\n", false);
    }

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

            if (!$this->valid) {
                $error = new ValidationError();
                $error->property = 'messageBody';
                $error->message = $this->differ->diff(
                    $response->getBody()->getContents(),
                    $request->getResponse()->getBody()
                );
                $this->errors[] = $error;
            }

            return;
        }

        $this->valid = true;
    }

    public function isValid()
    {
        return $this->valid;
    }

    public function getId()
    {
        return 'text_plain';
    }

    public function getName()
    {
        return 'Plain Text Validator';
    }

    public function getErrors()
    {
        return $this->errors;
    }
}