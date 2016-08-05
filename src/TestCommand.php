<?php
/**
 * @author    Hendrik Maus <aidentailor@gmail.com>
 * @since     2016-08-05
 * @copyright 2016 (c) Hendrik Maus
 * @license   All rights reserved.
 * @package   spas
 */

namespace Hmaus\Spas;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('test')
            ->setDescription('I will test your stuff');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Testing your test-cmd ... done</info>');
    }
}