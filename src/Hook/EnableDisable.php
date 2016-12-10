<?php
/**
 * Quickly enable/disable entire resources
 *
 * ```bash
 * --hook "\Hmaus\Spas\Hook\EnableDisable" \
 * --hook_data $'{
 *     "EnableDisable": {
 *         "disable": [
 *             "Part of the resource name",
 *             "you get the names from the log output",
 *             "be as precise as you can",
 *             "be less precise on purpose, to switch whole groups."
 *         ],
 *         "enable": [
 *             "Note that Spas will process only",
 *             "the requests, whose names contain",
 *             "the strings that are provided here."
 *         ]
 *     }
 * }'
 * ```
 */

namespace Hmaus\Spas\Hook;

use Hmaus\Spas\Event\BeforeAll;
use Hmaus\Spas\Event\BeforeEach;

class EnableDisable extends Hook
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

    private function onBeforeAll(BeforeAll $event)
    {
        $defaults = [
            'disable' => [],
            'enable'  => [],
        ];

        $this->bag->add(
            $this->hookHandler->getJsonHookDataWithDefaults(static::class, $defaults)
        );
    }

    private function onBeforeEach(BeforeEach $event)
    {
        $request = $event->getRequest();
        $name    = $request->getName();

        foreach ($this->bag->get('disable') as $disabled) {
            if (!$this->contains($disabled, $name)) {
                continue;
            }
            $request->setEnabled(false);
        }

        $count = 0;

        foreach ($this->bag->get('enable') as $enabled) {
            if (!$this->contains($enabled, $name)) {
                $count++;
            }
        }

        if ($count == count($this->bag->get('enable'))) {
            $request->setEnabled(false);
        }
    }
}
