<?php
/**
 * Hook Playground
 * ---------------
 *
 * Required hook data to be passed into the command:
 *
 * ```bash
 * --hook hooks/hook-playground.php \
 * --hook_data $'{
 *     "readme": {
 *         "description": "Pass any string into spas and it will be available using $this->getHookData()",
 *         "header": "X-Vnd-Api-Key"
 *     }
 * }'
 * ```
 *
 * Events can be found in Hmaus\Spas\Event\
 */

use Hmaus\Spas\Event\BeforeAll;
use Hmaus\Spas\Event\BeforeEach;
use Hmaus\Spas\Request\HookHandler;

/* Hooks are loaded by `Hmaus\Spas\Request\HookHandler`
 * use this line in every scope to get syntax completion: */
/** @var HookHandler $this */


/* The HookHandler provides a logger.
 * It is considered best practice, to prefix all hook log messages
 * so you can make out where messages are coming from later on: */
$this->getLogger()->info('Playground: loaded');


/* The HookHandler provides an event dispatcher: */
$this->getDispatcher();


/* Events are found in Hmaus\Spas\Event\ namespace and have a constant called NAME: */
$this->getDispatcher()->addListener(BeforeAll::NAME, function (BeforeAll $event)
{
    $this->getLogger()->info('Playground: before all fired');

    /* $event->getRequests() let's you access all requests spas will fire */
});


/* Let's skip a request: */
$this->getDispatcher()->addListener(BeforeEach::NAME, function(BeforeEach $event)
{
    $request = $event->getRequest();

    if ($request->getName() === 'Group > Resource > Name') {
        $request->setEnabled(false);
    }

    /* You can copy&paste the `Group > Resource > Name` from spas' console
     * output. Make sure to give proper naming to your resources in the api description */
});

