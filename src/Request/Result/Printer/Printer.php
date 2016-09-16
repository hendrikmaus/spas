<?php

namespace Hmaus\Spas\Request\Result\Printer;

interface Printer
{
    /**
     * Format and print the input
     * @param mixed $data
     * @param string $logLevel Loglevel from \Psr\Log\LogLevel
     * @return
     */
    public function print($data, $logLevel);
}