<?php

namespace Hmaus\Spas\Event;

use Hmaus\Spas\Parser\ParsedRequest;
use Symfony\Component\EventDispatcher\Event;

class BeforeAll extends Event
{
    const NAME = 'hmaus.spas.event.before_all';

    /**
     * @var array|\Hmaus\Spas\Parser\ParsedRequest[]
     */
    private $requests;

    /**
     * @param ParsedRequest[] $requests
     */
    public function __construct(array $requests)
    {
        $this->requests = $requests;
    }

    /**
     * Get all requests on this event
     *
     * @return ParsedRequest[]
     */
    public function getRequests() : array
    {
        return $this->requests;
    }

    /**
     * @param ParsedRequest[] $requests
     */
    public function setRequests(array $requests)
    {
        $this->requests = $requests;
    }
}
