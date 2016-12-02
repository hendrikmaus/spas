<?php
/**
 * Hello World Hook
 *
 * A simple hook example, logs a message before all requests execute.
 */

namespace Hmaus\Spas\Hook;

use Hmaus\Spas\Event\BeforeAll;

class HelloWorld extends Hook
{
    public function setup()
    {
        $this->dispatcher->addListener(BeforeAll::NAME, function(BeforeAll $event) {
            $this->onBeforeAll($event);
        });
    }

    private function onBeforeAll(BeforeAll $event)
    {
        $this->log('Hello World');
    }
}
