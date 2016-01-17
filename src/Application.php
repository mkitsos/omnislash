<?php

namespace Omnislash;

use Omnislash\Command\OmnislashCommand;
use Omnislash\Helper\GuzzleHelper;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\HelpCommand;

class Application extends BaseApplication
{
    const NAME = 'Omnislash - An HTTP/HTTPS stress tester';

    const VERSION = '0.1';

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct(static::NAME, static::VERSION);
    }

    /**
     * Always return our single command name.
     *
     * @param InputInterface $input
     * @return string
     */
    protected function getCommandName(InputInterface $input)
    {
        return 'omnislash';
    }

    /**
     * Add only the help command.
     *
     * @return array
     */
    protected function getDefaultCommands()
    {
        $defaultCommands = array(new HelpCommand());

        $defaultCommands[] = new OmnislashCommand();

        return $defaultCommands;
    }
    
    public function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();
        
        $helperSet->set(new GuzzleHelper());
        
        return $helperSet;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition()
    {
        return new InputDefinition(array(
            new InputOption('help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
            new InputOption('version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
            new InputOption('verbose', '-v', InputOption::VALUE_NONE, 'Increase the verbosity of messages'),
        ));
    }
}
