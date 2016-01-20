<?php

namespace Omnislash\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GuzzleHelper extends Helper
{
    /**
     * @var Client; 
     */
    protected $client = null;
    
    /**
     * @var ProgressBar
     */
    protected $progressBar = null;
    
    protected $output;
    protected $input;
    
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
     * Guzzle client options.
     * 
     * @var array
     */
    protected $options = [];
    
    public function request(InputInterface $input, OutputInterface $output)
    {
        $this->setInput($input);
        $this->setOutput($output);
        $this->setClient(new Client());
        
        print_r($input->getOptions()); exit;
         
        $this->setBaseUri($input->getArgument('URL'));
        $this->setOptions($input->getOptions());
        
        $progress = $this->getProgressBar($output, $this->getNumber());
        $requests = $this->getRequests($this->getBaseUri(), 'GET');
        
        $pool = $this->getPool(
            $this->getClient(),
            $requests,
            $this->getNumber(),
            $this->getConcurrency(),
            $progress
        );
        
        // Initiate the transfers and create a promise
        $promise = $pool->promise();
        // start and displays the progress bar
        $progress->start();
        // Force the pool of requests to complete.
        $promise->wait();
        // Finishes the progress output
        $progress->finish();
        // Append a newline
        $output->writeln('');
    }
    
    public function singleRequest(InputInterface $input, OutputInterface $output)
    {
        $base_uri = $input->getArgument('URL');
        
        $client = new Client(['base_uri' => $base_uri, 'http_errors' => false]);
        
        $response = $client->request('GET');
        
        $protocol = $response->getProtocolVersion();
        $code = $response->getStatusCode();
        $reason = $response->getReasonPhrase();
        
        $output->writeln('<info>HTTP/' . $protocol . '</> ' . $code . ' ' . $reason);
    }
    
    public function setOptions(array $options = [])
    {
        foreach ($options as $key => $value) {
            $pos = strpos($key, '-');
            
            // convert dashes to camel case
            if ($pos !== false) {
                $key = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $key))));
            }
            
            $method = 'set' . ucfirst($key);
            
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }
    
    public function getRequests($uri, $method)
    {
        return function ($total) use ($method, $uri) {
            for ($i = 0; $i < $total; $i++) {
                yield new Request($method, $uri);
            }
        };
    }
    
    public function getPool(
        Client $client,
        callable $requests,
        $total,
        $concurrency,
        ProgressBar $progress)
    {
        $pool = new Pool($client, $requests($total), [
            'concurrency' => $concurrency,
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
        
        return $pool;
    }
    
    public function getProgressBar(OutputInterface $output, $max)
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
        
        $progress = new ProgressBar($output, $max);
        
        $format = ' %current%/%max% [%bar%] %percent:3s%%';
        $format .= ' Fulfilled: %fulfilled% / Rejected: %rejected%';
        $format .= ' %elapsed:6s%/%estimated:-6s% %memory:6s%';
        
        $progress->setFormat($format);
        $progress->setBarCharacter('<comment>=</comment>');
        
        return $this->progressBar = $progress;
    }
    
    /**
     * @param ProgressBar $progressBar
     */
    public function setProgressBar(ProgressBar $progressBar)
    {
        $this->progressBar = $progressBar;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'guzzle';
    }
    
    public function setBaseUri($uri)
    {
        if (! filter_var($uri, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid url provided');
        }
        
        $this->baseUri = $uri;
    }
    
    public function getBaseUri()
    {
        return $this->baseUri;
    }
    
    public function getClient()
    {
        return $this->client;
    }
    
    public function setClient(Client $client)
    {
        $this->client = $client;
    }
    
    public function getInput()
    {
        if (! $this->input instanceof InputInterface) {
            throw new \InvalidArgumentException('No input interface specified');
        }
        
        return $this->input;
    }
    
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }
    
    public function getOutput()
    {
        if (! $this->output instanceof OutputInterface) {
            throw new \InvalidArgumentException('No output interface specified');
        }
        
        return $this->output;
    }
    
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }
    
    public function setNumber($number)
    {
        if (! is_numeric($number)) {
            throw new \InvalidArgumentException('Number of requests must be an integer');
        }
        
        $this->number = $number;
    }
    
    public function getNumber()
    {
        return $this->number;
    }
    
    public function setConcurrency($concurrency)
    {
        if (! is_numeric($concurrency)) {
            throw new \InvalidArgumentException('Number of concurrency must be an integer');
        }
        
        $this->concurrency = $concurrency;
    }
    
    public function getConcurrency()
    {
        return $this->concurrency;
    }
    
    public function setHttpMethod($method)
    {
        $this->httpMethod = $method;
    }
    
    public function getHttpMethod()
    {
        return $this->httpMethod;
    }
    
    public function setAuth($auth)
    {
        list($username, $password) = explode(':', $auth);
        
        $this->options['auth'] = [$username, $password];
    }
    
    public function getAuth()
    {
        return isset($this->options['auth']) ? $this->options['auth'] : null;
    }
    
    public function setAuthType($type)
    {
        if (!in_array($type, ['basic', 'digest'])) {
            throw new \InvalidArgumentException('Invalid auth-type provided');
        }
    }
}
