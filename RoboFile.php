<?php

class RoboFile extends \Robo\Tasks
{
    public function watch()
    {
        $this->test();

        $this
            ->taskWatch()
            ->monitor(
                ['src', 'tests', 'hooks'],
                function () {
                    $this->test();
                }
            )
            ->run();
    }

    public function test()
    {
        $this
            ->taskPHPUnit('vendor/bin/phpunit')
            ->run();
    }
}
