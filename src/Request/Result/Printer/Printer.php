<?php

namespace Hmaus\Spas\Request\Result\Printer;

interface Printer
{
    /**
     * Format and print the input
     *
     * @param mixed $data
     * @param string $logLevel Loglevel from \Psr\Log\LogLevel
     */
    public function printIt($data, $logLevel);

    /**
     * Get content type this printer is designed for
     *
     * @return string
     */
    public function getContentType();
}
