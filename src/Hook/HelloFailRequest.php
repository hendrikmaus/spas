<?php
/**
 * How a hook can tag a request as failed
 */

namespace Hmaus\Spas\Hook;

use Hmaus\Spas\Event\BeforeEach;

class HelloFailRequest extends Hook
{
    public function setup()
    {
        $this->dispatcher->addListener(BeforeEach::NAME, function(BeforeEach $event) {
            $this->onBeforeEach($event);
        });
    }

    private function onBeforeEach(BeforeEach $event)
    {
        $request = $event->getRequest();

        if ($request->getName() === '') {

        }
        $event->getRequest()->failed = true;
    }
}
