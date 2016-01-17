<?php

namespace Omnislash\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OmnislashCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('omnislash')
            ->addArgument(
                'host',
                InputArgument::REQUIRED,
                '[http[s]://]hostname[:port]/path'
            )
            ->addOption(
               'number',
               'n',
               InputOption::VALUE_REQUIRED,
               'Number of requests to perform for the benchmarking session',
               1
            )
            ->addOption(
               'concurrency',
               'c',
               InputOption::VALUE_REQUIRED,
               'Number of multiple requests to perform at a time. Default is one request at a time',
               1
            )
            ->addOption(
               'auth',
               'A',
               InputOption::VALUE_REQUIRED,
               'Supply BASIC Authentication credentials to the server'
            )
            ->addOption(
               'http-method',
               'm',
               InputOption::VALUE_REQUIRED,
               'HTTP method for the request',
               'GET'
            )
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Sending some requests');
        
        $guzzle = $this->getHelper('guzzle');
        $guzzle->request($input, $output);
    }
}