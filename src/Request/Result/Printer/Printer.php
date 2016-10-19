<?php

namespace Hmaus\Spas\Request\Result\Printer;

use Psr\Log\LoggerInterface;

class Printer
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Max characters to print
     *
     * @var int
     */
    private $maximumPrintLength = 300;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Format and print the input
     *
     * @param mixed $data
     * @param string $logLevel Loglevel from \Psr\Log\LogLevel
     */
     public function printIt($data, string $logLevel)
     {
         $this->log($logLevel, $data);
     }

    /**
     * Write to the console, auto-truncated
     *
     * @param string $logLevel
     * @param string $message
     */
    final protected function log(string $logLevel, string $message)
    {
        if ($this->maximumPrintLength > 0) {
            if (strlen($message) > $this->maximumPrintLength) {
                $message = sprintf(
                    "%s\n\n(truncated)\n", substr($message, 0, $this->maximumPrintLength)
                );
            }
        }

        $this->logger->log($logLevel, $message);
    }

    /**
     * Get content type this printer is designed for
     *
     * @return string
     * @throws \Exception
     */
    public function getContentType(): string
    {
        throw new \Exception('Implement ' . __FUNCTION__);
    }

    /**
     * @return int
     */
    public function getMaximumPrintLength(): int
    {
        return $this->maximumPrintLength;
    }

    /**
     * @param int $maximumPrintLength
     */
    public function setMaximumPrintLength(int $maximumPrintLength)
    {
        $this->maximumPrintLength = $maximumPrintLength;
    }
}
