<?php

namespace Hmaus\Spas\Tests\Logger;

use Hmaus\Spas\Logger\TruncateableConsoleLogger;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class TruncateableConsoleLoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OutputInterface|ObjectProphecy
     */
    private $output;

    /**
     * @var TruncateableConsoleLogger|ObjectProphecy
     */
    private $logger;

    protected function setUp()
    {
        $this->output = $this->prophesize(ConsoleOutput::class);
        $this->logger = new TruncateableConsoleLogger($this->output->reveal());

        $this
            ->output
            ->getErrorOutput()
            ->willReturn(
                $this->output->reveal()
            );

        $this
            ->output
            ->getVerbosity()
            ->willReturn(
                OutputInterface::VERBOSITY_NORMAL
            );
    }

    public function testBasicLoggerConfig()
    {
        $this->logger->setMaxLength(100);
        $this->assertSame(100, $this->logger->getMaxLength());

        $this->assertTrue($this->logger->isTruncating());
        $this->logger->setShouldTruncate(false);
        $this->assertFalse($this->logger->isTruncating());
    }

    public function testLoggerShouldTruncateAndMessageIsShortEnough()
    {
        $this
            ->output
            ->writeln(Argument::exact('<error>[error] log that shit</error>'), Argument::type('integer'))
            ->shouldBeCalledTimes(1);

        $this->logger->log('error', 'log that shit');
    }

    public function testLoggerShouldNotTruncate()
    {
        $this
            ->output
            ->writeln(Argument::exact('<error>[error] log that shit</error>'), Argument::type('integer'))
            ->shouldBeCalledTimes(1);

        $this
            ->logger
            ->setShouldTruncate(false);

        // set the max length to something very slim so the assertion doesn't have to be that long
        $this
            ->logger
            ->setMaxLength(10);

        $this->logger->log('error', 'log that shit');
    }

    public function testLoggerDoesNotTruncateWithMaxLengthInvalid()
    {
        $this
            ->output
            ->writeln(Argument::exact('<error>[error] log that shit</error>'), Argument::type('integer'))
            ->shouldBeCalledTimes(1);

        $this
            ->logger
            ->setShouldTruncate(true);

        $this
            ->logger
            ->setMaxLength(-10);

        $this->logger->log('error', 'log that shit');
    }

    public function testLoggerTruncatesLongMessages()
    {
        $this
            ->output
            ->writeln(Argument::containingString('(truncated)'), Argument::type('integer'))
            ->shouldBeCalledTimes(1);

        $this
            ->logger
            ->setShouldTruncate(true);

        $this
            ->logger
            ->setMaxLength(20);

        $this->logger->log('error', 'log that shit! log that shit! log that shit! ');
    }
}
