<?php
/**
 * Example on how to pass data from the outside into spas hooks, e.g. api key
 *
 * Best practice suggestion, is to use JSON format to pass data into your hooks like so:
 *
 * > Note: you use multiple `--hook` to add multiple hooks, use a **single** `--hook_data` to pass data
 * > into spas for use with all hooks
 *
 * Use your class shortname as key:
 * \Hmaus\Spas\Hook\HelloHookData > HelloHookData
 *
 * ```bash
 * --hook "\Hmaus\Spas\Hook\HelloHookData" \
 * --hook_data $'{
 *     "Hmaus-Spas-Hook-HelloHookData": {
 *         "apikey": "c3...452572"
 *     }
 * }'
 * ```
 */

namespace Hmaus\Spas\Hook;

use Hmaus\Spas\Event\BeforeAll;

class HelloHookData extends Hook
{
    public function setup()
    {
        $this->dispatcher->addListener(BeforeAll::NAME, function (BeforeAll $event) {
            $this->onBeforeAll($event);
        });
    }

    private function onBeforeAll(BeforeAll $event)
    {
        $handler = $this->hookHandler;

        // You can get your RAW data, in case you do not use JSON
        $handler->getRawHookData();

        // Get your decoded JSON data as assoc array
        $handler->getJsonHookData(static::class);

        // Define defaults here and apply them to the JSON data as you retrieve it
        $defaults = [
            'apikey' => '',
            'header' => 'X-Vnd-ApiKey'
        ];
        $data = $handler->getJsonHookDataWithDefaults(static::class, $defaults);

        $this->log($data['apikey']);
    }
}
