<?php

namespace Hmaus\Spas\Command;

use Hmaus\Spas\Filesystem\InputFinder;
use Hmaus\Spas\Parser\Parser;
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

    /**
     * @var InputFinder
     */
    private $inputFinder;

    public function __construct(ContainerInterface $container, InputFinder $inputFinder)
    {
        parent::__construct();

        $this->container = $container;
        $this->inputFinder = $inputFinder;
    }

    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Run test suite')
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'Path to the input JSON file to use'
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
                'Base URI to build requests with, e.g. https://example.com'
            )
            ->addOption(
                'request_parser',
                'p',
                InputOption::VALUE_REQUIRED,
                'Fully qualified class name for a spas-parser implementation, must be available in the autoloader',
                '\Hmaus\Spas\Parser\Apib'
            )
            ->addOption(
                'hook',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Path to hook file(s); use multiple --hook options to pass multiple files'
            )
            ->addOption(
                'hook_data',
                null,
                InputOption::VALUE_REQUIRED,
                'Data to inject into hooks; e.g. csv, json string, whatever your hooks understand'
            )
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Only run tests listed using filter option; use multiple --filter options to pass multiple filters'
            )
            ->addOption(
                'full_output',
                null,
                InputOption::VALUE_NONE,
                'Do not truncate log outputs, useful when running filtered commands for debugging and inspection'
            )
            ->addOption(
                'polling_count',
                null,
                InputOption::VALUE_REQUIRED,
                'How often spas should re-try pollage resources that return the HTTP Retry-After header',
                3
            )
            ->addOption(
                'all_transactions',
                null,
                InputOption::VALUE_NONE,
                'Whether to run all transactions for a resource
                
                By default, spas will only run the first transaction per resource, which is usually the happy case.
                If you intend to also run all the error/failures from your description, use this flag
                and provide hooks to manipulate the requests in order to make requests fail the way you expect'
            )
            ->setHelp(
                <<<'EOF'
                
Spas <info>%command.name%</info> builds HTTP requests from a given API Description
and sends them to the specified environment.

<comment>Usage flow:</comment>

  - Send your API Description through its respective parser
    to get the parse result as a json file
  - Call <info>spas run</info> (example below),
    add the respective spas-parser implementation so spas itself can 
    understand your parse result

<comment>Example Command:</comment>
  <info>
  spas run \
      --file "api-description.apib.refract.json" \
      --type apib \
      --base_uri http://localhost:8000 \
      --request_parser "Hmaus\Spas\Parser\Apib"
  </info>

To implement another request parser, please refer to the respective guide on:
<fg=blue>https://github.com/hendrikmaus/spas-parser</>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputPath = $this->getInputPath($input);
        $requestProviderClassName = $this->getRequestProvider($input);
        $jsonDecodedInputData = $this->getDecodedInputData($inputPath);
        $this->configureLogger($input);

        /** @var Parser $requestProvider */
        $requestProvider = new $requestProviderClassName();
        $requests = $requestProvider->parse($jsonDecodedInputData);

        $executor = $this->container->get('hmaus.spas.request.executor');
        $result = $executor->run($requests);

        return $result === true ? 0 : 1;
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    private function getInputPath(InputInterface $input) : string
    {
        $inputPath = $input->getOption('file');

        if (!$this->container->get('hmaus.spas.filesystem')->exists($inputPath)) {
            throw new InvalidOptionException(
                sprintf('Given input file "%s" does not exist.', $inputPath)
            );
        }
        return $inputPath;
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    private function getRequestProvider(InputInterface $input) : string
    {
        $parser = $input->getOption('request_parser');

        if (!class_exists($parser)) {
            throw new InvalidOptionException(
                sprintf(
                    'Could not load request provider class "%s"; is it available to the autoloader?',
                    $parser
                )
            );
        }
        return $parser;
    }

    /**
     * @param $inputPath
     * @return array
     */
    private function getDecodedInputData(string $inputPath) : array
    {
        $rawData = $this->inputFinder->getContents($inputPath);
        $decodedData = json_decode($rawData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidOptionException(
                sprintf('Given input file "%s" could not be json decoded', $inputPath)
            );
        }
        return $decodedData;
    }

    /**
     * @param InputInterface $input
     */
    private function configureLogger(InputInterface $input)
    {
        $fullLogging = $input->getOption('full_output');
        $this->container->get('hmaus.spas.logger')->setShouldTruncate(!$fullLogging);
    }
}
