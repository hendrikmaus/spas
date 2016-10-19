<?php

namespace Hmaus\Spas\Request\Result\Printer;

use Hmaus\Spas\Validation\Validator;

class ValidationReportPrinter extends Printer
{
    /**
     * @param Validator[] $data
     * @param string $logLevel
     */
    public function printIt($data, string $logLevel)
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

    public function getContentType():string
    {
        return 'application/vnd.hmaus.spas.validation_report';
    }

}
