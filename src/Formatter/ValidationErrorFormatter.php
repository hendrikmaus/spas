<?php

namespace Hmaus\Spas\Formatter;

use Hmaus\Spas\Validation\Validator;

class ValidationErrorFormatter implements Formatter
{
    public function format($data) : string
    {
        $result = [];

        /** @var Validator $validator */
        foreach ($data as $validator) {
            if ($validator->isValid()) {
                continue;
            }

            $result[] = sprintf("%s failed with:\n", $validator->getName());

            foreach ($validator->getErrors() as $error) {
                $result[] = sprintf("  Property: %s\n", $error->property);
                $result[] = sprintf("  Message : %s\n", $error->message);
                $result[] = "\n";
            }
        }

        // chop off the last \n
        array_pop($result);

        // chop off the \n from the last message
        $lastElement = array_pop($result);
        $lastElement = substr($lastElement, 0, mb_strlen($lastElement) - 1);
        $result[] = $lastElement;

        return implode("[error] ", $result);
    }

    public function getContentTypes()
    {
        return [
            'application/vnd.hmaus.spas.validation.error'
        ];
    }
}
