<?php

namespace Hmaus\Spas\Request\Result\Printer;

use Hmaus\Spas\Validation\Validator;
use Psr\Log\LoggerInterface;

class ValidationReportPrinter implements Printer
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Validator[] $data
     * @param string $logLevel
     */
    public function print($data, $logLevel)
    {
        $report = $data;
        foreach ($report as $validator) {
            if ($validator->isValid()) {
                continue;
            }

            $this->logger->log(
                $logLevel,
                sprintf('%s failed with:', $validator->getName())
            );
            $this->logger->log($logLevel, '');

            $errors = $validator->getErrors();

            // todo how will this look with the plaintext validator errors?

            foreach ($errors as $error) {
                $this->logger->log(
                    $logLevel,
                    sprintf(' Property: %s', $error->property)
                );
                $this->logger->log(
                    $logLevel,
                    sprintf('  Message: %s', $error->message)
                );
                $this->logger->log($logLevel, '');
            }
        }
    }
}