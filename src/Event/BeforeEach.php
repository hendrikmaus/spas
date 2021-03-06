<?php

namespace Hmaus\Spas\Event;

use Hmaus\Spas\Parser\ParsedRequest;
use Symfony\Component\EventDispatcher\Event;

class BeforeEach extends Event
{
    const NAME = 'hmaus.spas.event.before_each';

    /**
     * @var ParsedRequest
     */
    private $request;

    public function __construct(ParsedRequest $request)
    {
        $this->request = $request;
    }

    /**
     * @return ParsedRequest
     */
    public function getRequest(): ParsedRequest
    {
        return $this->request;
    }

    /**
     * @param ParsedRequest $request
     */
    public function setRequest(ParsedRequest $request)
    {
        $this->request = $request;
    }
}
