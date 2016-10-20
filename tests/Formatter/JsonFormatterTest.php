<?php

namespace Hmaus\Spas\Tests\Formatter;

use Hmaus\Spas\Formatter\JsonFormatter;

class JsonFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JsonFormatter
     */
    private $formatter;

    protected function setUp()
    {
        $this->formatter = new JsonFormatter();
    }

    public function testCanDetectIndentedJson()
    {
        $expected = '{
    "hello":"world"
}';

        $actual = $this->formatter->format($expected);
        $this->assertSame($expected, $actual);
    }

    public function testCanIndentJson()
    {
        $data = '{"hello":"world"}';
        $actual = $this->formatter->format($data);

        $expected = '{
    "hello":"world"
}';

        $this->assertSame($expected, $actual);
    }

    public function testCanHandleQuotedStrings()
    {
        $data = '{"hello":"\"world\""}';
        $actual = $this->formatter->format($data);

        $expected = '{
    "hello":"\"world\""
}';

        $this->assertSame($expected, $actual);
    }

    public function testCanIndentLargerAmount()
    {
        $data = '[';
        do {
            $data .= '{"key":"name","value":"testing"},';
        } while (strlen($data) < 150);
        $data .= ']';

        $expected = '[
    {
        "key":"name",
        "value":"testing"
    },
    {
        "key":"name",
        "value":"testing"
    },
    {
        "key":"name",
        "value":"testing"
    },
    {
        "key":"name",
        "value":"testing"
    },
    {
        "key":"name",
        "value":"testing"
    },
    
]';

        $actual = $this->formatter->format($data);

        $this->assertSame($expected, $actual);
    }

    public function testDoesKnowItsContentType()
    {
        $this->assertNotEmpty($this->formatter->getContentTypes());
    }
}
