<?php

namespace Hmaus\Spas\Request;


use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
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
    private $rawHookData;

    /**
     * @var ParameterBag
     */
    private $hookDataBag;

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
        $this->hookDataBag = new ParameterBag();
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
    public function getRawHookData() : string
    {
        if ($this->rawHookData !== null) {
            return $this->rawHookData;
        }

        $hookdata = $this->input->getOption('hook_data');

        if ($hookdata === null) {
            $this->rawHookData = '';
        }
        else {
            $this->rawHookData = $hookdata;
        }

        return $this->rawHookData;
    }

    public function getHookDataFromJson() : array
    {
        $data = $this->getRawHookData();
        $data = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error(
                'Hook Handler: Passed hook data failed in json decoding process: "{0}"', [json_last_error_msg()]
            );
            return [];
        }

        return $data;
    }


    /**
     * Helper method to get hook data using a specific key
     *
     * @param string $key Key to find the hook data in
     * @return array
     */
    public function getHookDataWithKey(string $key) : array
    {
        $data = $this->getHookDataFromJson();

        if (!isset($data[$key])) {
            $this->logger->warning('No hook data with key \'{0}\' found.',[$key]);
            return [];
        }

        return $data[$key];
    }

    /**
     * Helper method to apply defaults on top of incoming hook data
     *
     * For example, you are in a header hook and the data comes in as json:
     *
     *   {
     *       "header-hook": {
     *           "field1": true
     *       }
     *   }
     *
     * But you are expecting not only field1, but also field2.
     * You want to apply a default so your code can rely on field2 being there.
     *
     *   $data = HookHandler::getHookDataFromJson()
     *
     * Call the helper:
     *
     *   $defaults = [
     *       'field1' => false,
     *       'field2' => false
     *   ];
     *
     *   HookHandler::applyHookDataDefaults('header-hook', $defaults, $data)
     *
     * This will give you:
     *
     *   [
     *       'field1' => true,
     *       'field2' => false
     *   ]
     *
     * @param string $key Key to find the hook data in
     * @param array $defaults Defaults values to apply
     * @param array $data Hook data
     * @return array
     */
    public function applyHookDataDefaults(string $key, array $defaults, array $data) : array
    {
        if (isset($data[$key])) {
            return array_merge($defaults, $data[$key]);
        }

        return $defaults;
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

    /**
     * @return ParameterBag
     */
    public function getHookDataBag(): ParameterBag
    {
        return $this->hookDataBag;
    }

    /**
     * @param ParameterBag $hookDataBag
     */
    public function setHookDataBag(ParameterBag $hookDataBag)
    {
        $this->hookDataBag = $hookDataBag;
    }
}
