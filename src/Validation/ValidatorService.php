<?php

namespace Hmaus\Spas\Validation;

use Hmaus\Spas\Parser\ParsedRequest;
use Psr\Log\LoggerInterface;

class ValidatorService
{
    /**
     * @var LoggerInterface
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
     * @return $this
     */
    public function validate(ParsedRequest $request)
    {
        foreach ($this->validators as $validator) {
            $validator->validate($request, $request->getActualResponse());
            $this->report[$validator->getId()] = $validator;
        }

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
