<?php

namespace Hmaus\Spas\Tests\Formatter;

use Hmaus\Spas\Formatter\Formatter;
use Hmaus\Spas\Formatter\FormatterService;

class FormatterServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormatterService
     */
    private $service;

    protected function setUp()
    {
        $this->service = new FormatterService();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessageRegExp /Content-type "\w+" is not supported/
     */
    public function testThrowsExceptionIfContentTypeIsUnknown()
    {
        $this
            ->service
            ->getFormatterByContentType('something');
    }

    public function testCanAddFormatters()
    {
        $formatter0 = $this->prophesize(Formatter::class);
        $formatter0
            ->getContentTypes()
            ->willReturn([
                "application/vnd.amazing.zero",
                "application/vnd.awesome.zero"
            ]);

        $formatter1 = $this->prophesize(Formatter::class);
        $formatter1
            ->getContentTypes()
            ->willReturn([
                "application/vnd.amazing.one",
                "application/vnd.awesome.one"
            ]);

        $this
            ->service
            ->addFormatter($formatter0->reveal());

        $this
            ->service
            ->addFormatter($formatter1->reveal());

        $this->assertInstanceOf(
            Formatter::class,
            $this->service->getFormatterByContentType('application/vnd.amazing.zero')
        );

        $this->assertInstanceOf(
            Formatter::class,
            $this->service->getFormatterByContentType('application/vnd.awesome.zero')
        );

        $this->assertInstanceOf(
            Formatter::class,
            $this->service->getFormatterByContentType('application/vnd.amazing.one')
        );

        $this->assertInstanceOf(
            Formatter::class,
            $this->service->getFormatterByContentType('application/vnd.awesome.one')
        );
    }

}
