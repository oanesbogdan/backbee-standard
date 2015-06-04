<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee Standard Edition.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee Standard Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee Standard Edition. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Standard\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use BackBee\Console\AbstractCommand;

/**
 * Welcome command
 * @author Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
 */
class WelcomeCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('demo:welcome')
            ->setDescription('Welcome someone')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Who do you want to welcome?'
            )
            ->addOption(
                'yell',
                null,
                InputOption::VALUE_NONE,
                'If set, the message will yell in uppercase letters'
            )
            ->setHelp(<<<EOF
The <info>%command.name%</info> command can welcome someone.
With the option ``yell``, the message will be displayed in uppercase letters.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    { 
        $text = 'Welcome '.$input->getArgument('name');

        if ($input->getOption('yell')) {
            $text = strtoupper($text);
        }

        $output->writeln($text);
    }
}