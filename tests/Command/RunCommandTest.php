<?php

namespace Hmaus\Spas\Tests\Command {
    use Hmaus\Spas\Command\RunCommand;
    use Hmaus\Spas\Filesystem\InputFinder;
    use Hmaus\Spas\Logger\TruncateableConsoleLogger;
    use Hmaus\Spas\Request\Executor;
    use Prophecy\Argument;
    use Prophecy\Prophecy\ObjectProphecy;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    use Symfony\Component\DependencyInjection\ContainerInterface;
    use Symfony\Component\Filesystem\Filesystem;

    class RunCommandTest extends \PHPUnit_Framework_TestCase
    {
        /**
         * @var ContainerInterface|ObjectProphecy
         */
        private $container;

        /**
         * @var InputInterface|ObjectProphecy
         */
        private $input;

        /**
         * @var OutputInterface|ObjectProphecy
         */
        private $output;

        /**
         * @var Executor|ObjectProphecy
         */
        private $executor;

        /**
         * @var Filesystem|ObjectProphecy
         */
        private $filesystem;

        /**
         * @var RunCommand
         */
        private $command;

        /**
         * @var InputFinder|ObjectProphecy
         */
        private $inputFinder;

        /**
         * Filename used in all the test cases
         * **Do not change**
         *
         * @var string
         */
        private $filename = '/i/am/file';

        /**
         * @var TruncateableConsoleLogger|ObjectProphecy
         */
        private $logger;

        protected function setUp()
        {
            $this->container = $this->prophesize(ContainerInterface::class);
            $this->inputFinder = $this->prophesize(InputFinder::class);
            $this->input = $this->prophesize(InputInterface::class);
            $this->output = $this->prophesize(OutputInterface::class);
            $this->executor = $this->prophesize(Executor::class);
            $this->filesystem = $this->prophesize(Filesystem::class);
            $this->logger = $this->prophesize(TruncateableConsoleLogger::class);

            $this
                ->container
                ->get(Argument::exact('hmaus.spas.filesystem'))
                ->willReturn(
                    $this->filesystem->reveal()
                );

            $this
                ->container
                ->get(Argument::exact('hmaus.spas.request.executor'))
                ->willReturn(
                    $this->executor->reveal()
                );

            $this
                ->container
                ->get(Argument::exact('hmaus.spas.logger'))
                ->willReturn(
                    $this->logger->reveal()
                );

            $this->command = new RunCommand(
                $this->container->reveal(),
                $this->inputFinder->reveal()
            );
        }

        /**
         * @expectedException \Symfony\Component\Console\Exception\InvalidOptionException
         * @expectedExceptionMessage Given input file "/i/am/file"
         */
        public function testThrowsExceptionIfInputPathDoesNotExist()
        {
            $this
                ->input
                ->getOption(Argument::exact('file'))
                ->willReturn($this->filename);

            $this
                ->filesystem
                ->exists(Argument::exact($this->filename))
                ->willReturn(false);

            $this->callExecute();
        }

        /**
         * @expectedException \Symfony\Component\Console\Exception\InvalidOptionException
         * @expectedExceptionMessage Could not load request provider class
         */
        public function testThrowsExceptionIfRequestProviderDoesNotExist()
        {
            // Make `getInputPath` pass
            $this
                ->input
                ->getOption(Argument::exact('file'))
                ->willReturn($this->filename);

            $this
                ->filesystem
                ->exists(Argument::exact($this->filename))
                ->willReturn(true);

            // error out in `getRequestProvider`
            $this
                ->input
                ->getOption(Argument::exact('request_provider'))
                ->willReturn(null);

            $this->callExecute();
        }

        /**
         * @expectedException \Symfony\Component\Console\Exception\InvalidOptionException
         * @expectedExceptionMessage Given input file "/i/am/file" could not be json decoded
         */
        public function testThrowsExceptionIfInputDataCannotBeReadAsString()
        {
            // Make `getInputPath` pass
            $this
                ->input
                ->getOption(Argument::exact('file'))
                ->willReturn($this->filename);

            $this
                ->filesystem
                ->exists(Argument::exact($this->filename))
                ->willReturn(true);

            // Make `getRequestProvider` pass
            $this
                ->input
                ->getOption(Argument::exact('request_provider'))
                ->willReturn('\Hmaus\Spas\Parser\Apib\ApibParsedRequestsProvider');

            // Error out in `getDecodedInputData`
            $this
                ->inputFinder
                ->getContents(Argument::exact($this->filename))
                ->willReturn(false);

            $this->callExecute();
        }

        public function testHappyCase()
        {
            // Make `getInputPath` pass
            $this
                ->input
                ->getOption(Argument::exact('file'))
                ->willReturn($this->filename);

            $this
                ->filesystem
                ->exists(Argument::exact($this->filename))
                ->willReturn(true);

            // Make `getRequestProvider` pass
            $this
                ->input
                ->getOption(Argument::exact('request_provider'))
                ->willReturn('\Hmaus\Spas\Parser\Apib\ApibParsedRequestsProvider');

            $this
                ->input
                ->getOption(Argument::exact('full_output'))
                ->willReturn(true);

            // Make `getDecodedInputData` pass
            $this
                ->inputFinder
                ->getContents(Argument::exact($this->filename))
                ->willReturn('{
  "element": "parseResult",
  "content": [
    {
      "element": "category",
      "meta": {
        "classes": [
          "api"
        ],
        "title": ""
      },
      "content": []
    }
  ]
}');

            $result = $this->callExecute();

            $this->assertSame(0, $result);
        }

        private function getExecuteMethod() : \ReflectionMethod
        {
            $reflection = new \ReflectionClass(RunCommand::class);
            $method = $reflection->getMethod('execute');
            $method->setAccessible(true);
            return $method;
        }

        private function callExecute() : int
        {
            $input  = $this->input->reveal();
            $output = $this->output->reveal();
            $method = $this->getExecuteMethod();
            return $method->invokeArgs($this->command, [$input, $output]);
        }

    }
}

