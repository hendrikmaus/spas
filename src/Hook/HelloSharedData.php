<?php
/**
 * Hello Data Hook
 *
 * This hook shows how to share data between events within the same hook,
 * e.g. onBeforeAll you could retrieve an oAuth token, store it and re-use it in
 * every onBeforeEach event
 *
 * Another possibility is to even share data across all other hooks, by putting
 * the data onto the hookhandler.
 */

namespace Hmaus\Spas\Hook;

use Hmaus\Spas\Event\BeforeAll;
use Hmaus\Spas\Event\BeforeEach;

class HelloSharedData extends Hook
{
    public function setup()
    {
        $this->dispatcher->addListener(BeforeAll::NAME, function(BeforeAll $event) {
            $this->onBeforeAll($event);
        });

        $this->dispatcher->addListener(BeforeEach::NAME, function(BeforeEach $event) {
            $this->onBeforeEach($event);
        });
    }

    private function onBeforeAll(BeforeAll $event)
    {
        // Local data bag within this very hook
        $this->bag->set('hello', 'world');

        // Global data bag, shared across all hooks
        $this->hookHandler->getHookDataBag()->set('all', 'can access this');
    }

    private function onBeforeEach(BeforeEach $event)
    {
        $this->log('Hello {0}', [
            $this->bag->get('hello')
        ]);
    }
}
