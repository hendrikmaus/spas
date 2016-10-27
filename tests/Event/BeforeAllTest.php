<?php

namespace Hmaus\Spas\Tests\Event;

use Hmaus\Spas\Event\BeforeAll;
use Hmaus\Spas\Parser\SpasRequest;

class BeforeAllTest extends \PHPUnit_Framework_TestCase
{
    public function testCanSetAndGetRequestCollection()
    {
        $requests = [
            new SpasRequest(),
            new SpasRequest(),
        ];

        $event = new BeforeAll($requests);

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
