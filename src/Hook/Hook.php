<?php

namespace Hmaus\Spas\Hook;

use Hmaus\Spas\Request\HookHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class Hook
{
    /**
     * Access to the hookhandler instance
     *
     * @var HookHandler
     */
    protected $hookHandler;

    /**
     * Easy access to the event dispatcher; one can also go through the hook handler
     *
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * A per-hook param bag to share state between events in the same hook
     * To share data bestween all hooks, use the param bag on the hook handler
     *
     * @var ParameterBag
     */
    protected $bag;

    public function __construct(
        HookHandler $handler,
        EventDispatcher $dispatcher,
        LoggerInterface $logger,
        ParameterBag $bag
    )
    {
        $this->hookHandler = $handler;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->bag = $bag;
    }

    /**
     * The setup method must be implemented and is called by the hook handler upon initialization
     * Usually, setup method will only contain the registration of event listeners.
     *
     * Note: don't fetch your hook data in `setup`, register a BeforeAll event
     *
     * @return void
     */
    abstract public function setup();

    /**
     * Logging helper, prefixes every log message with the hook class name
     *
     * @param string $msg    Your log message
     * @param array $context Array of vars to put into the placeholders inside the log message
     *                       E.g.: $msg = "Hello {0}, {1}"; $context = ["Spas", "greetings!]
     * @param string $level  Log level to use, defaults to info
     */
    protected function log(string $msg, array $context = [], string $level = LogLevel::INFO)
    {
        $this->hookHandler->getLogger()->log(
            $level,
            sprintf('%s: %s', $this->getShortName(), $msg),
            $context
        );
    }

    /**
     * Helper to get short class name of concrete implementations
     * @return string
     */
    protected function getShortName() : string
    {
        return (new \ReflectionClass(static::class))->getShortName();
    }
}
