<?php
/**
 * Format given input to proper display on the console using the console logger
 */

namespace Hmaus\Spas\Formatter;

interface Formatter
{
    /**
     * Format data for the console logger
     *
     * @param mixed $data Input for the formatter to output on the shell
     * @return string Formatted string for console output
     */
    public function format($data) : string;

    /**
     * Formatters can support a variety of content-types
     *
     * Return an array with a structure of:
     * ```php
     * ['type1', 'type2']
     * ```
     *
     * @return array
     */
    public function getContentTypes();
}
