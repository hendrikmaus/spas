<?php

namespace Hmaus\Spas\Tests\Request;

use Hmaus\Spas\Request\FilterHandler;
use Hmaus\Spas\Parser\ParsedRequest;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Input\InputInterface;

class FilterHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InputInterface|ObjectProphecy
     */
    private $input;

    /**
     * @var FilterHandler
     */
    private $filterHandler;

    protected function setUp()
    {
        $this->input = $this->prophesize(InputInterface::class);

        $this->filterHandler = new FilterHandler(
            $this->input->reveal()
        );
    }

    public function testDoesNothingWithoutFilterStringInput()
    {
        $this
            ->input
            ->getOption('filter')
            ->willReturn(null);

        $request = $this->prophesize(ParsedRequest::class);
        $request
            ->getName()
            ->shouldNotBeCalled();
        
        $this
            ->filterHandler
            ->filter($request->reveal());
    }

    public function testRequestStaysUntouchedIfItDoesntMatchFilterStringInput()
    {
        $filters = ['The > Request > Name'];

        $this
            ->input
            ->getOption('filter')
            ->willReturn($filters);

        $request = $this->prophesize(ParsedRequest::class);
        $request
            ->getName()
            ->willReturn($filters[0])
            ->shouldBeCalledTimes(1);

        $request
            ->setEnabled(Argument::type('bool'))
            ->shouldNotBeCalled();

        $this
            ->filterHandler
            ->filter($request->reveal());
    }

    public function testRequestIsDisbaledIfItDoesntMatch()
    {
        $filters = ['The > Request > Name'];

        $this
            ->input
            ->getOption('filter')
            ->willReturn($filters);

        $request = $this->prophesize(ParsedRequest::class);
        $request
            ->getName()
            ->willReturn('Another > Request')
            ->shouldBeCalledTimes(1);

        $request
            ->setEnabled(false)
            ->shouldBeCalledTimes(1);

        $this
            ->filterHandler
            ->filter($request->reveal());
    }

}
