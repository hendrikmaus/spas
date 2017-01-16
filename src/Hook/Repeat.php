<?php
/**
 * Repeat requests as they are and/or manipulate params and headers for each run
 *
 * Provide `times` if you want to repeat the request as is.
 * If you add params and headers, the request will be repeated by the amount pf params.
 *   The amount of params and headers has to be the same!
 *
 * ```bash
 * --hook "\Hmaus\Spas\Hook\Repeat" \
 * --hook_data $'{
 *    "Hmaus-Spas-Hook-Repeat": [
 *         {
 *             "name" : "Health",
 *             "times": 1
 *         },
 *         {
 *             "name": "Locations",
 *             "params": [
 *                 {"query":"New York"},
 *                 {"query":"Berlin"},
 *                 {"query":"Big Apple"}
 *             ]
 *         },
 *         {
 *             "name": "Secure",
 *             "headers": [
 *                 {"X-Vnd-Api-Key": "a working key"},
 *                 {"X-Vnd-Api-Key": "another working key"}
 *             ]
 *         }
 *     ]
 * ```
 *
 * If you want to assert the outcome of different param sets,
 * you could try to extend this hook. That is why all the methods are protected
 * rather than private.
 */
namespace Hmaus\Spas\Hook;

use Hmaus\Spas\Event\BeforeAll;
use Hmaus\Spas\Event\BeforeEach;

class Repeat extends Hook
{
    public function setup()
    {
        $this->dispatcher->addListener(BeforeAll::NAME, function (BeforeAll $event) {
            $this->onBeforeAll($event);
        });

        $this->dispatcher->addListener(BeforeEach::NAME, function (BeforeEach $event) {
            $this->onBeforeEach($event);
        });
    }

    protected function onBeforeAll(BeforeAll $event)
    {
        $data = $this->hookHandler->getJsonHookData(static::class);

        $defaults = [
            'name' => '',
            'params' => [],
            'headers' => [],
            'times' => 0
        ];

        foreach ($data as $key => $value) {
            $data[$key] = array_merge($defaults, $value);
        }

        $this->bag->add($data);
    }

    protected function onBeforeEach(BeforeEach $event)
    {
        $request  = $event->getRequest();
        $config   = $request->getRepetitionConfig();
        $name     = strtolower($request->getName());

        foreach ($this->bag->all() as $repeat) {
            if ($this->doesNotMatch($name, $repeat)) {
                continue;
            }

            if (count($repeat['params']) !== 0) {
                $request->getParams()->add($repeat['params'][$config->count]);
            }

            if (count($repeat['headers'])) {
                $request->getHeaders()->add($repeat['headers'][$config->count]);
            }

            if ($config->repeat) {
                continue;
            }

            $config->repeat = true;

            // If `times` is set, use the value
            if ($repeat['times'] !== 0) {
                $config->times = $repeat['times'];
            }

            // Or set it by the amount of param entries we have
            if (count($repeat['params']) !== 0) {
                $config->times = count($repeat['params']) - 1;
            }
        }

        if ($this->shouldNotRepeatAgain($config)) {
            $config->repeat = false;
        }
    }

    /**
     * @param $name
     * @param $repeat
     * @return bool
     */
    protected function doesNotMatch($name, $repeat): bool
    {
        return strpos($name, strtolower($repeat['name'])) === false;
    }

    /**
     * @param $config
     * @return bool
     */
    protected function shouldNotRepeatAgain($config): bool
    {
        return $config->count > 0 && ($config->count === $config->times);
    }
}
