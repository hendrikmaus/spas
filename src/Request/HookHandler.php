<?php

namespace Hmaus\Spas\Request;

use Hmaus\Spas\Hook\Hook;
use Psr\Log\LoggerInterface;
use Seld\JsonLint\JsonParser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\ParameterBag;

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

    /**
     * @var JsonParser
     */
    private $jsonParser;

    public function __construct(
        InputInterface $input,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        Filesystem $filesystem,
        ParameterBag $parameterBag,
        JsonParser $jsonParser
    )
    {
        $this->input = $input;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->hookDataBag = $parameterBag;
        $this->jsonParser = $jsonParser;
    }

    public function includeHooks()
    {
        foreach ($this->getHookFiles() as $hookname) {
            if (class_exists($hookname)) {
                /** @var Hook $hook */
                $hook = new $hookname($this, $this->dispatcher, $this->logger, new ParameterBag());
                $hook->setup();
                continue;
            }

            // @deprecated
            // legacy hooks to be removed with spas 1.0
            if (!$this->filesystem->exists($hookname)) {
                $this->logger->warning('Hook file could not be loaded:');
                $this->logger->warning('  "{0}"', [$hookname]);
                $this->logger->warning('Make sure the path is correct and readable');
                continue;
            }

            /** @noinspection PhpIncludeInspection */
            include $hookname;
            // @deprecated
        }
    }

    public function getHookFiles(): array
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
    public function getRawHookData(): string
    {
        if ($this->rawHookData !== null) {
            return $this->rawHookData;
        }

        $hookdata = $this->input->getOption('hook_data');

        if ($hookdata === null) {
            $this->rawHookData = '';
        } else {
            $this->rawHookData = $hookdata;
        }

        return $this->rawHookData;
    }

    /**
     * @param string $key optional key to get from the data-set
     * @return array
     */
    public function getJsonHookData(string $key = ''): array
    {
        $data = $this->getRawHookData();

        try {
            $data = $this->jsonParser->parse($data, JsonParser::PARSE_TO_ASSOC);
        } catch (\Exception $exception) {
            $this->logger->error('Hook Handler: {0}', [$exception->getMessage()]);
            return [];
        }

        if ($key === '') {
            return $data;
        }

        $key = str_replace('\\','-', $key);

        if (!isset($data[$key])) {
            $this->logger->warning(
                'Hook Handler: "{0}" was not found in hook data', [$key]
            );
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
     * @return array
     */
    public function getJsonHookDataWithDefaults(string $key, array $defaults = []): array
    {
        return array_merge($defaults, $this->getJsonHookData($key));
    }

    /**
     * @return array
     * @deprecated use \Hmaus\Spas\Request\HookHandler::getJsonHookData instead; removed with spas 1.0
     */
    public function getHookDataFromJson(): array
    {
        return $this->getJsonHookData();
    }

    /**
     * Helper method to get hook data using a specific key
     *
     * @param string $key Key to find the hook data in
     * @return array
     * @deprecated use \Hmaus\Spas\Request\HookHandler::getJsonHookData instead; removed with spas 1.0
     */
    public function getHookDataWithKey(string $key): array
    {
        return $this->getJsonHookData($key);
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
     * @deprecated use \Hmaus\Spas\Request\HookHandler::getJsonHookDataWithDefaults instead; removed with spas 1.0
     */
    public function applyHookDataDefaults(string $key, array $defaults, array $data): array
    {
        if (isset($data[$key])) {
            return array_merge($defaults, $data[$key]);
        }

        return $defaults;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher(): EventDispatcherInterface
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
