<?php

namespace Hmaus\Spas\Tests\Event;

use Hmaus\Spas\Event\BeforeEach;
use Hmaus\Spas\Parser\SpasRequest;

class BeforeEachTest extends \PHPUnit_Framework_TestCase
{
    public function testCanSetAndGetRequest()
    {
        $request = new SpasRequest();
        $event = new BeforeEach($request);

        $this->assertSame($request, $event->getRequest());

        $event->setRequest($request);
        $this->assertSame($request, $event->getRequest());
    }
}
