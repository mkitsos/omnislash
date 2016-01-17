<?php

namespace Omnislash\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GuzzleHelper extends Helper
{
    /**
     * @var \GuzzleHttp\Client; 
     */
    protected $client = null;
    
    public static $fulfilled = 0;
    public static $rejected = 0;
    
    /**
     * @var string
     */
    protected $baseUri;
    
    protected $number;
    protected $concurrency;
    protected $httpMethod;
    
    /**
     * @var ProgressBar
     */
    protected $progressBar = null;
    
    protected $output;
    protected $input;

    public function request(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
        
        //set arguments
        $this->baseUri = $input->getArgument('host');
        
        // set options
        $this->setOptions($input->getOptions());
        
        $progress = $this->getProgressBar();
        // Initiate the transfers and create a promise
        $promise = $this->getPromise($progress);
        // start and displays the progress bar
        $progress->start();
        // Force the pool of requests to complete.
        $promise->wait();
        // Finishes the progress output
        $progress->finish();
    }
    
    protected function getPromise($progress)
    {
        $base_uri = $this->baseUri;
        $method = $this->httpMethod;
        
        $requests = function ($total) use ($base_uri, $method) {
            for ($i = 0; $i < $total; $i++) {
                yield new Request($method, $base_uri);
            }
        };
        
        $client = new Client();
        
        $pool = new Pool($client, $requests($this->number), [
            'concurrency' => $this->concurrency,
            'fulfilled' => function ($response, $index) use ($progress) {
                // this is delivered each successful response
                $progress->advance();
                \Omnislash\Helper\GuzzleHelper::$fulfilled++;
            },
            'rejected' => function ($reason, $index) use ($progress) {
                // this is delivered each failed request
                $progress->advance();
                \Omnislash\Helper\GuzzleHelper::$rejected++;
            }
        ]);
        
        return $pool->promise();
    }
    
    protected function setOptions(array $options)
    {
        if ((isset($options['number']))) {
            $this->number = $options['number'];
        }
        
        if (isset($options['concurrency'])) {
            $this->concurrency = $options['concurrency'];
        }
        
        if (isset($options['http-method'])) {
            $this->httpMethod = $options['http-method'];
        }
    }
    
    public function getProgressBar()
    {
        if ($this->progressBar instanceof ProgressBar) {
            return $this->progressBar;
        }
        
        ProgressBar::setPlaceholderFormatterDefinition(
            'rejected',
            function (ProgressBar $bar) {
                return \Omnislash\Helper\GuzzleHelper::$rejected;
            }
        );
        
        ProgressBar::setPlaceholderFormatterDefinition(
            'fulfilled',
            function (ProgressBar $bar) {
                return \Omnislash\Helper\GuzzleHelper::$fulfilled;
            }
        );
        
        $progress = new ProgressBar($this->output, $this->number);
        
        $format = ' %current%/%max% [%bar%] %percent:3s%%';
        $format .= ' Fulfilled: %fulfilled% / Rejected: %rejected%';
        $format .= ' %elapsed:6s%/%estimated:-6s% %memory:6s%';
        
        $progress->setFormat($format);
        $progress->setBarCharacter('<comment>=</comment>');
        
        return $this->progressBar = $progress;
    }
    
    public function setProgressBar(ProgressBar $progressBar)
    {
        $this->progressBar = $progressBar;
    }
    
    protected function setBaseUri($uri)
    {
        $this->baseUri = $uri;
    }
    
    public function getBaseUri()
    {
        return $this->baseUri;
    }

    public function getName()
    {
        return 'guzzle';
    }
}