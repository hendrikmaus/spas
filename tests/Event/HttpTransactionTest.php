<?php

namespace Hmaus\Spas\Tests\Event;

use Hmaus\Spas\Event\HttpTransaction;
use Hmaus\SpasParser\SpasRequest;

class HttpTransactionTest extends \PHPUnit_Framework_TestCase
{
    public function testCanSetAndGetRequest()
    {
        $request = new SpasRequest();
        $event = new HttpTransaction($request);

        $this->assertSame($request, $event->getRequest());

        $event->setRequest($request);
        $this->assertSame($request, $event->getRequest());
    }
}
