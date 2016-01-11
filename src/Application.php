<?php

namespace Omnislash;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Omnislash\Command\SlashCommand;
use Symfony\Component\Console\Command\HelpCommand;

class Application extends BaseApplication
{
    const APP_NAME = 'Omnislash - An HTTP/HTTPS stress tester';
    
    const APP_VERSION = '0.1';
    
    public function __construct()
    {
        parent::__construct(static::APP_NAME, static::APP_VERSION);
    }
    
    protected function getCommandName(InputInterface $input)
    {
        return 'slash';
    }
    
    protected function getDefaultCommands()
    {
        // Keep the core default commands to have the HelpCommand
        // which is used when using the --help option
        $defaultCommands = array(new HelpCommand());

        $defaultCommands[] = new SlashCommand();

        return $defaultCommands;
    }
    
    /**
     * Overridden so that the application doesn't expect the command
     * name to be the first argument.
     */
    public function getDefinition()
    {
        $inputDefinition = parent::getDefinition();
        // clear out the normal first argument, which is the command name
        $inputDefinition->setArguments();

        return $inputDefinition;
    }
}