<?php

namespace Hmaus\Spas\Request\Options;

class Repetition
{
    /**
     * Whether to repeat this request
     * @var bool
     */
    public $repeat = false;

    /**
     * How often was this request repeated
     * @var int
     */
    public $count = 0;

    /**
     * How often should the request be repeated
     * @var int
     */
    public $times = 0;
}
