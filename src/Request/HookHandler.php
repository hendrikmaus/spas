<?php

namespace Hmaus\Spas\Request;


use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

class HookHandler
{
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $hookData;

    public function __construct(
        InputInterface $input,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        Filesystem $filesystem
    )
    {
        $this->input = $input;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->filesystem = $filesystem;
    }

    public function includeHooks()
    {
        foreach ($this->getHookFiles() as $hookfile) {
            if (!$this->filesystem->exists($hookfile)) {
                $this->logger->warning('Hook file could not be loaded:');
                $this->logger->warning('  "{0}"', [$hookfile]);
                $this->logger->warning('Make sure the path is correct and readable');
                continue;
            }

            /** @noinspection PhpIncludeInspection */
            include $hookfile;
        }
    }

    public function getHookFiles() : array
    {
        $hookfiles = $this->input->getOption('hook');

        if (count($hookfiles) === 0) {
            $this->logger->info('No hooks loaded');

            return [];
        }

        return $hookfiles;
    }

    /**
     * Get hook data string passed into the command
     *
     * @return string
     */
    public function getHookData() : string
    {
        if ($this->hookData !== null) {
            return $this->hookData;
        }

        // todo decorate the input and add a default value parameter to the getOption method
        $hookdata = $this->input->getOption('hook_data');

        if ($hookdata === null) {
            $this->hookData = '';
        }
        else {
            $this->hookData = $hookdata;
        }

        return $this->hookData;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger() : LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher() : EventDispatcherInterface
    {
        return $this->dispatcher;
    }
}
