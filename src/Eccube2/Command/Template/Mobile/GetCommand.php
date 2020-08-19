<?php

/*
 * This file is part of EC-CUBE2 CLI.
 *
 * (C) Tsuyoshi Tsurushima.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube2\Command\Template\Mobile;

use Eccube2\Init;
use Eccube2\Util\Template;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends Command
{
    protected static $defaultName = 'template:mobile:get';

    /** @var Template */
    protected $template;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();

        $this->template = new Template();
    }

    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $code = $this->template->getMobileTemplate();

        $output->writeln('    <info>' . $code . '</info>');
    }
}
