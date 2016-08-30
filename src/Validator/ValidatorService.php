<?php
/**
 * @author    Hendrik Maus <aidentailor@gmail.com>
 * @since     2016-08-13
 * @copyright 2016 (c) Hendrik Maus
 * @license   All rights reserved.
 * @package   spas
 */

namespace Hmaus\Spas\Validator;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Hmaus\SpasParser\ParsedRequest;
use Symfony\Bridge\Monolog\Logger;

class ValidatorService
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Validator[]
     */
    private $validators = [];

    /**
     * @var array
     */
    private $report = [];

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Run the validator chain
     *
     * @param ParsedRequest $request
     * @param GuzzleResponse $response
     * @return $this
     */
    public function validate(ParsedRequest $request, GuzzleResponse $response)
    {
        foreach ($this->validators as $validator) {
            $validator->validate($request, $response);
            $response->getBody()->rewind(); // to be able to retrieve the body again and again
            $this->report[$validator->getName()] = $validator->isValid();
        }

        // todo add header validator
        // todo allow hooks to add in validators
        // todo think of a strategy to validate responses with content but no schema for 'em

        return $this;
    }

    /**
     * @return boolean
     */
    public function isValid(): bool
    {
        foreach ($this->report as $result) {
            if (!$result) return false;
        }

        return true;
    }

    /**
     * Called by compiler pass to add tagged validators
     *
     * @param Validator $validator
     */
    public function addValidator(Validator $validator)
    {
        $this->validators[] = $validator;
    }

    /**
     * Resets the validator for next run
     */
    public function reset()
    {
        $this->report = [];
    }

    /**
     * @return array
     */
    public function getReport()
    {
        return $this->report;
    }
}