<?php

/**
 * Hook Playground
 * ---------------
 *
 * Pass the path to this file to spas.
 * Spas will provide an object in `$dispatcher` of type \Symfony\Component\EventDispatcher\EventDispatcherInterface
 *
 * todo list all possible events here
 * todo maybe event auto gen this example file? all the events can be collected by symfony
 * todo it would be nice to get syntax completion for EventDispatcherInterface and Event classes
 * todo we should isolate the hook files, currently they are simply included and get access to everything
 *
 * Example listener:
 * ```php
 * $dispatcher->addListener('hmaus.spas.event.before_all', function($event) {
 *     dump($event);
 * });
 * ```
 */

use Hmaus\Spas\Event\HttpTransaction;

if (!isset($dispatcher)) {
    throw new \Exception('Dispatcher not found');
}

$dispatcher->addListener('hmaus.spas.event.before_all', function($event) {
    $this->logger->info('Before All triggered');
});

$dispatcher->addListener('hmaus.spas.event.after_all', function($event) {
    $this->logger->info('After All triggered');
});

$dispatcher->addListener(
    HttpTransaction::NAME, function(HttpTransaction $event) {
        $request = $event->getRequest();

    /*if ($request->getName() === 'Coupons > Coupon > Retrieve a Coupon') {
        $request->setEnabled(false);
    }*/
});

/**
 * Generate Signature for each request
 * todo omit `/health` resource
 */
/*$dispatcher->addListener(
    HttpTransaction::NAME,
    function (HttpTransaction $event) {
        $publicKey = '211d7860ff5f4f79a4a388e7737c2f6c';
        $privateKey = '4f0288ba61c4c936827c258f5478bd3576b2c0819b0cc9903c392d6b7986d312';
        $request = $event->getRequest();

        if ($request->getName() === 'General > Health Check > Health Check') {
            $request->setEnabled(false);
            return;
        }

//        $acceptedUrls = [
//            'General > Entry Point > Retrieve the Entry Point',
//            'Hotel Search > Locations > Locations',
//            'Hotel Search > Hotels Collection > Hotels Collection'
//        ];
//
//        if (!in_array($request->getName(), $acceptedUrls)) {
//            $request->setEnabled(false);
//            return;
//        }

        $method = $request->getMethod();
        $url = $request->getBaseUrl().$request->getRequestUri();
        $signedUrl = create_signed_url($method, $url, $publicKey, $privateKey);
        $this->logger->info(
            sprintf('Signed URL: %s', $signedUrl)
        );

        $signedUrl = str_replace($request->getBaseUrl(), '', $signedUrl);
        $signedUrl = str_replace('https://trivago.local/webservice/tas', '', $signedUrl);

        $request->setUri($signedUrl);
    }
);*/

/**
 * Signs a GET request for the trivago API.
 *
 * Returns the URL with the appended signature.
 *
 * @param string $method
 * @param string $url
 * @param string $access_id
 * @param string $secret_key
 *
 * @return string
 */
function create_signed_url(string $method, string $url, string $access_id, string $secret_key): string {
    $url_info = parse_url($url);

    if (!isset($url_info['query'])) {
        $url_info['query'] = '';
    }

    $query_parameters = [];

    parse_str($url_info['query'], $query_parameters);

    $query_parameters['timestamp'] = date(DATE_ATOM);
    $query_parameters['access_id'] = $access_id;

    ksort($query_parameters);

    $string_to_sign = implode("\n", [
        $method,
        $url_info['host'],
        rtrim($url_info['path'], '/'),
        http_build_query($query_parameters)
    ]);

    $query_parameters['signature'] = base64_encode(hash_hmac('sha256', $string_to_sign, $secret_key, true));

    return $url_info['scheme'] . '://' . $url_info['host'] . $url_info['path'] . '?' . http_build_query($query_parameters);
}