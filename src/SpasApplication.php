<?php

namespace Hmaus\Spas;

use Hmaus\Spas\Formatter\CompilerPass\FormatterPass;
use Hmaus\Spas\Logger\TruncateableConsoleLogger;
use Hmaus\Spas\Validation\CompilerPass\AddValidatorsPass;
use Psr\Log\LogLevel;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * @codeCoverageIgnore
 */
class SpasApplication extends Application
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    public function __construct($name, $version)
    {
        parent::__construct($name, $version);

        $this->initContainer();
        $this->setupCommands();
    }

    private function initContainer()
    {
        $this->container = new ContainerBuilder();
        $loader = new XmlFileLoader($this->container, new FileLocator(__DIR__));
        $loader->load('Resources/config/services.xml');
        $loader->load('Resources/config/commands.xml');
        $loader->load('Resources/config/validators.xml');

        $this->container->addCompilerPass(new AddValidatorsPass());
        $this->container->addCompilerPass(new FormatterPass());
    }

    private function setupCommands()
    {
        $this->add($this->container->get('hmaus.spas.command.run'));
    }

    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $input) {
            $input = new ArgvInput();
        }
        $this->container->set('hmaus.spas.console_input', $input);

        if (null === $output) {
            $output = new ConsoleOutput();
        }
        $this->container->set('hmaus.spas.console_output', $output);

        $logger = new TruncateableConsoleLogger(
            $output,
            [
                LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
            ],
            [
                LogLevel::WARNING => 'comment',
                LogLevel::NOTICE => 'fg=blue'
            ]
        );
        $this->container->set('hmaus.spas.logger', $logger);

        $io = new SymfonyStyle($input, $output);
        $this->container->set('hmaus.spas.io', $io);

        $this->container->set('hmaus.spas.spas_application', $this);

        // make sure to compile the container so compiler passes run
        $this->container->compile();

        return parent::run($input, $output);
    }
}
