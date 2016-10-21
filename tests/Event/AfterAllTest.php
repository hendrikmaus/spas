<?php

namespace Hmaus\Spas\Tests\Event;

use Hmaus\Spas\Event\AfterAll;
use Hmaus\SpasParser\SpasRequest;

class AfterAllTest extends \PHPUnit_Framework_TestCase
{
    public function testCanSetAndGetRequestCollection()
    {
        $requests = [
            new SpasRequest(),
            new SpasRequest(),
        ];

        $event = new AfterAll($requests);

        $this->assertSame($requests, $event->getRequests());

        $requests = [
            new SpasRequest(),
            new SpasRequest(),
            new SpasRequest(),
        ];

        $event->setRequests($requests);

        $this->assertSame($requests, $event->getRequests());
    }
}
