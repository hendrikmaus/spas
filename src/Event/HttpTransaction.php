<?php

namespace Hmaus\Spas\Event;

use Hmaus\SpasParser\ParsedRequest;
use Symfony\Component\EventDispatcher\Event;

class HttpTransaction extends Event
{
    const NAME = 'hmaus.spas.event.http_transaction';

    /**
     * @var ParsedRequest
     */
    private $request;

    public function __construct(ParsedRequest $request)
    {
        $this->request = $request;
    }

    /**
     * @param ParsedRequest $request
     * @return HttpTransaction
     */
    public function setRequest(ParsedRequest $request): HttpTransaction
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return ParsedRequest
     */
    public function getRequest(): ParsedRequest
    {
        return $this->request;
    }
}