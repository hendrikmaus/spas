<?php

namespace Hmaus\Spas\Request\Result\Printer;

class UniversalPrinter extends Printer
{
    public function printIt($data, string $logLevel)
    {
        $this->log($logLevel, $data);
    }

    /**
     * When a printer client cannot make out a content-type
     * one can set the content-type to the string of 'null'
     * and try to use this universal printer to display
     * at least something.
     *
     * @inheritdoc
     * @return string
     */
    public function getContentType() : string
    {
        return 'null';
    }
}
