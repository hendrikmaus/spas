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
use Hmaus\Spas\Request\Options\Repetition;

/* Hooks are loaded by `Hmaus\Spas\Request\HookHandler`
 * use this line in every scope to get syntax completion: */
/** @var HookHandler $this */


/* The HookHandler provides a logger.
 * It is considered best practice, to prefix all hook log messages
 * so you can make out where messages are coming from later on: */
$this->getLogger()->info('Playground: loaded');


/* The HookHandler provides an event dispatcher: */
$this->getDispatcher();


/* The HookHandler provides the raw input hook data and a parameter bag to store your data across hooks/events */
$this->getRawHookData();
$bag = $this->getHookDataBag();
$bag->set('my_key', 'my_value');
/* You ahve to take care of putting your data into the bag */


/* Events are found in Hmaus\Spas\Event\ namespace and have a constant called NAME: */
$this->getDispatcher()->addListener(BeforeAll::NAME, function (BeforeAll $event)
{
    /** @var HookHandler $this */
    
    $this->getLogger()->info('Playground: before all fired');

    /* $event->getRequests() let's you access all requests spas will fire */
    
    /* hook data is accessible */
    $this->getLogger()->info(
        'Playground: "{0}" is shared with this event',
        [$this->getHookDataBag()->get('my_key')]
    );
});


/* Let's skip a request: */
$this->getDispatcher()->addListener(BeforeEach::NAME, function(BeforeEach $event)
{
    /** @var HookHandler $this */
    
    $request = $event->getRequest();

    if ($request->getName() === 'Group > Resource > Name') {
        $request->setEnabled(false);
    }

    /* You can copy&paste the `Group > Resource > Name` from spas' console
     * output. Make sure to give proper naming to your resources in the api description */

    /* hook data is accessible */
    /*$this->getLogger()->info(
        'Playground: "{0}" is shared with this event',
        [$this->getHookDataBag()->get('my_key')]
    );*/
});

/* let's repeat a request */
$this->getDispatcher()->addListener(BeforeEach::NAME, function(BeforeEach $event)
{
    /** @var HookHandler $this */

    $request = $event->getRequest();

    if ($request->getName() !== 'Group > Resource > Name') {
        return;
    }

    /** @var Repetition $repetitionConfig */
    $repetitionConfig = $request->getProcessorOptions()->get(Repetition::class);

    if ($repetitionConfig->times === 0) {
        $repetitionConfig->times = 3;
    }

    $repetitionConfig->repeat = true;

    $this->getLogger()->info('Playground: Counted ' . ($repetitionConfig->count + 1) . ' hits');
});

