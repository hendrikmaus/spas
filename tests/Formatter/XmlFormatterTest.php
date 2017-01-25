<?php

namespace Hmaus\Spas\Tests\Formatter;

use Hmaus\Spas\Formatter\XmlFormatter;

class XmlFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var XmlFormatter
     */
    private $formatter;

    protected function setUp()
    {
        $this->formatter = new XmlFormatter();
    }

    public function testCanDetectXml()
    {
        $expected = '<?xml version="1.0" encoding="UTF-8"?>
<note>
  <from>Batman</from>
  <to>Robin</to>
  <message>boo</message>
</note>
';

        $actual = $this->formatter->format($expected);

        $this->assertSame($expected, $actual);
    }

    public function testDetectsNonXml()
    {
        $nonXmlString = 'batman';

        try {
            $this->formatter->format($nonXmlString);
            $this->assertTrue(false);
        } catch (\Exception $exception) {
            $this->assertSame('DOMDocument::loadXML(): Start tag expected, \'<\' not found in Entity, line: 1', $exception->getMessage());
        }
    }

    public function testDetectsIncorrectXml()
    {
        $incorrectXmlString = '<?xml version="1.0" encoding="UTF-8"?>
<note>
  <from>Bat';

        try {
            $this->formatter->format($incorrectXmlString);
            $this->assertTrue(false);
        } catch (\Exception $exception) {
            $this->assertSame('DOMDocument::loadXML(): Premature end of data in tag from line 3 in Entity, line: 3', $exception->getMessage());
        }
    }

    public function testDoesKnowItsContentType()
    {
        $this->assertNotEmpty($this->formatter->getContentTypes());
    }
}