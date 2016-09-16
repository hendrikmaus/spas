<?php

namespace Hmaus\Spas\Command;

use Hmaus\SpasParser\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @codeCoverageIgnore
 */
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
                'file',
                // todo should also support reading from stdin; one lies to pipe content in
                'f',
                InputOption::VALUE_REQUIRED,
                'Path to the input file to use'
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_REQUIRED,
                'Type of input, e.g. `apib`',
                'apib'
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
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Path to hook file(s)'
            )
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Only run tests listed using filter option'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputPath = $input->getOption('file');

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

        $rawInputData = file_get_contents($inputPath);

        if ($rawInputData === false) {
            throw new InvalidOptionException(
                sprintf('Given input file "%s" could not be read as string.', $inputPath)
            );
        }

        $jsonDecodedInputData = json_decode($rawInputData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidOptionException(
                sprintf('Given input file "%s" could not be json decoded', $inputPath)
            );
        }

        /** @var Parser $requestProvider */
        $requestProvider = new $requestProviderClassName();
        $requests = $requestProvider->parse($jsonDecodedInputData);

        $executor = $this->container->get('hmaus.spas.request.executor');
        $executor->run($requests, $input, $output);

        // todo event to propagate the report

        return 0;
    }
}
