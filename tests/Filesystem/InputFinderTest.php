<?php

namespace Hmaus\Spas\Tests\Filesystem;

use Hmaus\Spas\Filesystem\InputFinder;

class InputFinderTest extends \PHPUnit_Framework_TestCase
{
    public function testCanReadFiles()
    {
        $finder = new InputFinder();
        $this->assertNotEmpty($finder->getContents(realpath(__FILE__)));
    }
}
