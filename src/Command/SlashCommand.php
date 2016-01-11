<?php

namespace Omnislash\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SlashCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('slash')
            ->setDescription('Send multiple create payment requests')
            ->addArgument(
                'host',
                InputArgument::OPTIONAL,
                '[http[s]://]hostname[:port]/path'
            )
            ->addOption(
               'number',
               null,
               InputOption::VALUE_REQUIRED,
               'Number of requests to perform'
            )
            ->addOption(
               'concurrency',
               null,
               InputOption::VALUE_REQUIRED,
               'Number of multiple requests to make at a time'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Nevermind...');
    }
}
