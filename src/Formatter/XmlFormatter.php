<?php

namespace Hmaus\Spas\Formatter;

class XmlFormatter implements Formatter
{
    public function format($data) : string
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = FALSE;
        $dom->loadXML($data);
        $dom->formatOutput = TRUE;
        return $dom->saveXml();
    }

    public function getContentTypes()
    {
        return [
            'application/xml',
            'text/xml'
        ];
    }
}
