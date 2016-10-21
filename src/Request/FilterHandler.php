<?php

namespace Hmaus\Spas\Request;

use Hmaus\SpasParser\ParsedRequest;
use Symfony\Component\Console\Input\InputInterface;

class FilterHandler
{
    /**
     * @var InputInterface
     */
    private $input;

    public function __construct(InputInterface $input)
    {
        $this->input = $input;
    }

    public function filter(ParsedRequest $request)
    {
        $filters = $this->input->getOption('filter');

        if (!$filters) {
            return;
        }

        /*
         * Name Filter:
         * if the name of the request is not exactly stated in the filter string,
         * the request will be deactivated
         */
        if (!in_array($request->getName(), $filters)) {
            $request->setEnabled(false);
        }

        // todo URL Filter
        // todo Improve Name Filter to support partial matches
        // todo phpunit's filter option might be worth inspecting to learn something
        // todo depending on how many filters we'll get, factor them out into individual objects
    }
}
