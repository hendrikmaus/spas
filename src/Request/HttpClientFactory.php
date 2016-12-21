<?php

namespace Hmaus\Spas\Request;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use React\EventLoop\Factory;
use WyriHaximus\React\GuzzlePsr7\HttpClientAdapter;

class HttpClientFactory
{
    public static function create(string $type)
    {
        if ($type === 'curl') {
            return new Client();
        }

        if ($type === 'react') {
            $loop    = Factory::create();
            $handler = new HttpClientAdapter($loop);
            $client  = new Client([
                'handler' => HandlerStack::create($handler)
            ]);

            return $client;
        }

        throw new \Exception('Unknown client type requested');
    }
}
