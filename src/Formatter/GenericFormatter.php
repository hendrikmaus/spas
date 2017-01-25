<?php

namespace Hmaus\Spas\Formatter;

class GenericFormatter implements Formatter
{
    public function format($data): string
    {
        return $data;
    }

    public function getContentTypes()
    {
        return [
            'text/plain'
        ];
    }
}
