<?php

namespace Hmaus\Spas\Request\Result;

class ProcessorReport
{
    /**
     * @var int
     */
    private $failed = 0;

    /**
     * @var int
     */
    private $passed = 0;

    /**
     * @var int
     */
    private $disabled = 0;

    /**
     * @var string[]
     */
    private $processed = [];

    /**
     * Increment failed count
     */
    public function failed()
    {
        $this->failed += 1;
    }

    /**
     * Increment passed count
     */
    public function passed()
    {
        $this->passed += 1;
    }

    /**
     * Increment disabled count
     */
    public function disabled()
    {
        $this->disabled += 1;
    }

    /**
     * Mark request name as processed
     * @param string $name
     */
    public function processed(string $name)
    {
        $this->processed[] = $name;
    }

    /**
     * @return bool
     */
    public function hasFailures() : bool
    {
        return $this->failed !== 0;
    }

    /**
     * @return array
     */
    public function getProcessedList() : array
    {
        return $this->processed;
    }

    /**
     * Check whether a request was processed by its full name
     * @param string $name
     * @return bool
     */
    public function wasProcessed(string $name): bool
    {
        return in_array($name, $this->getProcessedList());
    }

    /**
     * Get count of passed tests
     * @return int
     */
    public function getPassed() : int
    {
        return $this->passed;
    }

    /**
     * Get count of failed tests
     * @return int
     */
    public function getFailed() : int
    {
        return $this->failed;
    }

    /**
     * Get count of disabled tests
     * @return int
     */
    public function getDisabled() : int
    {
        return $this->disabled;
    }
}
