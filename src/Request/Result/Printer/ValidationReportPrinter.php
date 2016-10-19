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

            $this->log(
                $logLevel,
                sprintf('%s failed with:', $validator->getName())
            );
            $this->log($logLevel, '');

            $errors = $validator->getErrors();

            foreach ($errors as $error) {
                $this->log(
                    $logLevel,
                    sprintf(' Property: %s', $error->property)
                );
                $this->log(
                    $logLevel,
                    sprintf('  Message: %s', $error->message)
                );
                $this->log($logLevel, '');
            }
        }
    }

    public function getContentType():string
    {
        return 'application/vnd.hmaus.spas.validation_report';
    }

}
