<?php

namespace Hmaus\Spas\Request\Result\Printer;

use Psr\Log\LoggerInterface;

class UniversalPrinter implements Printer
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function printIt($data, $logLevel)
    {
        $this->logger->log($logLevel, (string) $data);
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
    public function getContentType()
    {
        return 'null';
    }
}
