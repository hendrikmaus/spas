<?php
/**
 * @author    Hendrik Maus <aidentailor@gmail.com>
 * @since     2016-08-05
 * @copyright 2016 (c) Hendrik Maus
 * @license   All rights reserved.
 * @package   spas
 */

namespace Hmaus\Spas\Tests;

use Hmaus\Spas\TestCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class TestCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $app = new Application();
        $app->add(new TestCommand());

        $cmd = $app->find('test');
        $cmdTester = new CommandTester($cmd);
        $cmdTester->execute(['command' => $cmd->getName()]);

        $output = $cmdTester->getDisplay();
        $this->assertContains('Testing', $output);
    }
}
