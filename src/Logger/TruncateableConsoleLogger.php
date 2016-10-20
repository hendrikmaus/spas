<?php

namespace Hmaus\Spas\Logger;

use Symfony\Component\Console\Logger\ConsoleLogger;

class TruncateableConsoleLogger extends ConsoleLogger
{
    private $maxLength = 300;
    private $shouldTruncate = false;

    public function log($level, $message, array $context = [])
    {
        parent::log($level, $this->process($message), $context);
    }

    private function process(string $message) : string
    {
        return $this->isTruncationEnabled($message);
    }

    private function isTruncationEnabled(string $message) : string
    {
        if ($this->shouldTruncate === false) {
            return $message;
        }

        return $this->isMaxLengthValid($message);
    }

    private function isMaxLengthValid(string $message) : string
    {
        if ($this->maxLength <= 0) {
            return $message;
        }

        return $this->isMessageTooLong($message);
    }

    private function isMessageTooLong(string $message) : string
    {
        if (mb_strlen($message) <= $this->maxLength) {
            return $message;
        }

        return $this->truncate($message);
    }

    private function truncate(string $message) : string
    {
        return sprintf("%s\n\n(truncated)\n", substr($message, 0, $this->maxLength));
    }

    /**
     * @return int
     */
    public function getMaxLength(): int
    {
        return $this->maxLength;
    }

    /**
     * @param int $maxLength
     */
    public function setMaxLength(int $maxLength)
    {
        $this->maxLength = $maxLength;
    }

    /**
     * @return boolean
     */
    public function isTruncating(): bool
    {
        return $this->shouldTruncate;
    }

    /**
     * @param boolean $shouldTruncate
     */
    public function setShouldTruncate(bool $shouldTruncate)
    {
        $this->shouldTruncate = $shouldTruncate;
    }
}
