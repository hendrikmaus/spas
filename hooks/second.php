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
 *
 * Example listener:
 * ```php
 * $dispatcher->addListener('hmaus.spas.event.before_all', function($event) {
 *     dump($event);
 * });
 * ```
 */

if (!isset($dispatcher)) {
    throw new \Exception('Dispatcher not found');
}

$dispatcher->addListener('hmaus.spas.event.before_all', function($event) {
    $this->logger->info('Before All triggered from second hook');
});

$dispatcher->addListener('hmaus.spas.event.after_all', function($event) {
    $this->logger->info('After All triggered from second hook');
});