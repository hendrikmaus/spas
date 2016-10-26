<?php

namespace Hmaus\Spas\Validation;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Hmaus\Spas\Parser\ParsedRequest;
use Psr\Log\LoggerInterface;
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
     * @var Validator[]
     */
    private $report = [];

    public function __construct(LoggerInterface $logger)
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
            $this->report[$validator->getId()] = $validator;
        }

        // todo add header validator
        // todo allow hooks to add in validators
        // todo think of a strategy to validate responses with content but no schema

        return $this;
    }

    /**
     * @return boolean
     */
    public function isValid(): bool
    {
        foreach ($this->report as $validator) {
            if (!$validator->isValid()) {
                return false;
            }
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
     * @return Validator[]
     */
    public function getValidators() : array
    {
        return $this->validators;
    }

    /**
     * Resets the validator report for next run and all validators respectively
     */
    public function reset()
    {
        $this->report = [];

        foreach ($this->validators as $validator) {
            $validator->reset();
        }
    }

    /**
     * @return Validator[]
     */
    public function getReport() : array
    {
        return $this->report;
    }

    public function getContentType()
    {
        return 'application/vnd.hmaus.spas.validation.error';
    }
}
