<?php

namespace Hmaus\Spas\Formatter;

class FormatterService
{
    /**
     * @var Formatter[]
     */
    private $formatters = [];

    /**
     * Add formatters to the service.
     * Done by a compiler pass
     *
     * @param Formatter $formatter
     */
    public function addFormatter(Formatter $formatter)
    {
        foreach ($formatter->getContentTypes() as $supportedType) {
            $this->formatters[$supportedType] = $formatter;
        }
    }

    /**
     * Retrieve the correct formatter by content-type string, e.g. "application/json"
     *
     * @param string $type
     * @return Formatter
     * @throws \Exception
     */
    public function getFormatterByContentType(string $type) : Formatter
    {
        foreach ($this->formatters as $formatter) {
            foreach ($formatter->getContentTypes() as $contentType) {
                if (strpos($type, $contentType) !== false) {
                    return $formatter;
                }
            }
        }

        throw new \Exception(
            sprintf('Content-type "%s" is not supported', $type)
        );
    }
}
