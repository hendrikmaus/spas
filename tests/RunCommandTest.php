<?php
/**
 * @author    Hendrik Maus <aidentailor@gmail.com>
 * @since     2016-08-05
 * @copyright 2016 (c) Hendrik Maus
 * @license   All rights reserved.
 * @package   spas
 */

namespace Hmaus\Spas\Tests;

use GuzzleHttp\Client;
use Hmaus\DrafterPhp\Drafter;
use Hmaus\Pinako\Elements\ParseResult\ParseResultElement;
use Hmaus\Pinako\RefractNamespace;
use Hmaus\Spas\RunCommand;
use JsonSchema\Validator;
use Prophecy\Argument;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class RunCommandTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->markTestSkipped();
    }

    public function testExecute()
    {
        $exampleapib = 'example.apib';

        $drafter = $this->prophesize(Drafter::class);
        $drafter
            ->getBinary()
            ->willReturn('path/to/drafter');

        $drafter
            ->input(Argument::exact($exampleapib))
            ->willReturn($drafter);

        $drafter
            ->format(Argument::exact('json'))
            ->willReturn($drafter);

        $drafter
            ->run()
            ->willReturn(
                json_encode(['parseResult'=>'i am api'])
            );

        $validator = $this->prophesize(Validator::class);
        $httpClient = $this->prophesize(Client::class);

        $parser = $this->prophesize(RefractNamespace::class);
        $parser
            ->fromRefract(Argument::any())
            ->willReturn(new ParseResultElement());

        $app = new Application();

        $app->add(new RunCommand(
            $drafter->reveal(),
            $validator->reveal(),
            $httpClient->reveal(),
            $parser->reveal()
        ));

        $cmd = $app->find('run');
        $cmdTester = new CommandTester($cmd);
        $cmdTester->execute([
            'command' => $cmd->getName(),
            'apib' => 'example.apib'
        ]);

        $output = $cmdTester->getDisplay();
        $this->assertContains('Testing', $output);
    }
}
