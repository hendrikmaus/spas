<?php

namespace Hmaus\Spas\Request\Result\Printer;

use Psr\Log\LoggerInterface;

class JsonPrinter implements Printer
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Max characters to print
     * @var int
     */
    private $maximumPrintLength = 300;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $data
     * @param string $logLevel
     */
    public function printIt($data, $logLevel)
    {
        $prettyBody = $this->indent($data);

        if (strlen($prettyBody) > $this->maximumPrintLength) {
            $prettyBody = sprintf(
                "%s\n\n(truncated)\n", substr($prettyBody, 0, $this->maximumPrintLength)
            );
        }

        $this->logger->log($logLevel, $prettyBody);
    }

    /**
     * Indents a flat JSON string to make it more human-readable.
     *
     * @param string $json The original JSON string to process.
     *
     * @return string Indented version of the original JSON string.
     */
    private function indent($json)
    {
        $result = '';
        $pos = 0;
        $strLen = strlen($json);
        $indentStr = '    ';
        $newLine = "\n";
        $prevChar = '';
        $outOfQuotes = true;
        for ($i = 0; $i <= $strLen; $i++) {
            // Grab the next character in the string.
            $char = substr($json, $i, 1);
            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

                // If this character is the end of an element,
                // output a new line and indent the next line.
            } else {
                if (($char == '}' || $char == ']') && $outOfQuotes) {
                    $result .= $newLine;
                    $pos--;
                    for ($j = 0; $j < $pos; $j++) {
                        $result .= $indentStr;
                    }
                }
            }

            // Add the character to the result string.
            $result .= $char;
            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
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

    public function getContentType()
    {
        return 'application/json';
    }

}
