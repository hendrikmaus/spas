<?php

namespace Hmaus\Spas\Tests\Event;

use Hmaus\Spas\Event\AfterEach;
use Hmaus\Spas\Parser\SpasRequest;

class AfterEachTest extends \PHPUnit_Framework_TestCase
{
    public function testCanSetAndGetRequest()
    {
        $request = new SpasRequest();
        $event = new AfterEach($request);

        $this->assertSame($request, $event->getRequest());

        $event->setRequest($request);
        $this->assertSame($request, $event->getRequest());
    }
}
