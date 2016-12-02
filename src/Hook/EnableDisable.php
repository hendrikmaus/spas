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
 *             "be less precise on purpose, to switch whole groups
 *         ],
 *         "enable": [
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

        foreach ($this->bag->get('disable') as $disbaled) {
            if (!$this->contains($disbaled, $name)) {
                continue;
            }
            $request->setEnabled(false);
        }

        foreach ($this->bag->get('enable') as $enabled) {
            if (!$this->contains($enabled, $name)) {
                continue;
            }
            $request->setEnabled(true);
        }
    }
}
