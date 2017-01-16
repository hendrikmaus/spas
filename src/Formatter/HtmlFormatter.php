<?php

namespace Hmaus\Spas\Formatter;

class HtmlFormatter implements Formatter {

    public function format($data): string {
        return $data;
    }

    public function getContentTypes() {
        return [
            'text/html'
        ];
    }
}
