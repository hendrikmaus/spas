<?php

namespace Hmaus\Spas\Command;

use Hmaus\SpasParser\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RunCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();

        $this->container = $container;
    }

    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Run test suite')
            ->addOption(
                'input',
                // todo spas should not only support file, but also read from stdout so we can pipe the results from drafter right in
                'i',
                InputOption::VALUE_REQUIRED,
                'Path to the input file to use'
            )
            ->addOption(
                'input_type',
                't',
                InputOption::VALUE_REQUIRED,
                'Type of input, e.g. `apib-refract`'
            )
            ->addOption(
                'base_uri',
                'b',
                InputOption::VALUE_REQUIRED,
                'Base URI to build requests with'
            )
            ->addOption(
                'request_provider',
                'p',
                InputOption::VALUE_REQUIRED,
                'Fully qualified class name for the request provider, must be available in autoloader'
            )
            ->addOption(
                'hook',
                'x',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Path to hook file(s)'
            )
            ->addOption(
                'filter',
                'f',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Only run tests listed using filter option'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputPath = $input->getOption('input');

        if (!$this->container->get('hmaus.spas.filesystem')->exists($inputPath)) {
            throw new InvalidOptionException(
                sprintf('Given input file "%s" does not exist.', $inputPath)
            );
        }

        $requestProviderClassName = $input->getOption('request_provider');

        if (!class_exists($requestProviderClassName)) {
            throw new InvalidOptionException(
                sprintf(
                    'Could not load %s; make sure to have it available using the autloader',
                    $requestProviderClassName
                )
            );
        }

        /** @var Parser $requestProvider */
        $requestProvider = new $requestProviderClassName();
        $requests = $requestProvider->parse(
            json_decode(file_get_contents($inputPath), true)
        );

        $executor = $this->container->get('hmaus.spas.request.executor');
        $executor->run($requests, $input, $output);

        // todo event to propagate the report

        return 0;
    }
}