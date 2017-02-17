<?php

namespace SilverStripeSentry\Adaptors;

use SilverStripeSentry\Adaptors\ClientAdaptor;

/**
 * The Sentry class simply acts as a bridge between the Raven PHP SDK and
 * SilverStripe itself.
 * 
 * @author Russell Michell 2017 <russ@theruss.com>
 * @package silverstripe/sentry
 */

class RavenClient extends ClientAdaptor
{    
    /**
     *
     * @var Raven_Client
     */
    protected $client;
    
    /**
     * A mapping of log-level values between Zend_Log => Raven_Client
     * 
     * @var array
     */
    protected $logLevels = [
        'NOTICE'    => \Raven_Client::INFO,
        'WARN'      => \Raven_Client::WARNING,
        'ERR'       => \Raven_Client::ERROR,
        'EMERG'     => \Raven_Client::FATAL
    ];
    
    /**
     * It's an ERROR unless proven otherwise!
     * 
     * @var string
     */
    private static $default_error_level = 'ERROR';
    
    /**
     * 
     * @param array $userData
     * @return \Raven_Client
     */
    public function __construct(array $userData = [])
    {        
        $dsn = $this->opts('dsn');
        $this->client = new \Raven_Client($dsn);
        
        if (!is_null($env = $this->getEnv())) {
            $this->client->setEnvironment($env);
        }
        
        if ($userData) {
            $this->client->user_context([
                'email' => $this->formatExtras($userData, 'email')
            ]);
        }
        
        if ($userData) {
            $this->setUserData($userData);
        }
        
        // Installs all available PHP error handlers
        if ($this->config()->install === true) {
            $this->client->install();
        }
        
        return $this->client;
    }
    
    /**
     * @return string
     */
    public function level($level)
    {
        return isset($this->client->logLevels[$level]) ?
            $this->client->logLevels[$level] : 
            $this->client->logLevels[self::$default_error_level];
    }
    
    /**
     * Physically transport the data to the configured Sentry host.
     * 
     * @param string $message
     * @param array $extras
     * @param sarray $data
     * @param string $trace
     * @return mixed
     */
    public function send($message, $extras = [], $data, $trace)
    {
        // Raven_Client::captureMessage() returns an ID to uniquely identify each message sent to Sentry
        $eventId = $this->client->captureMessage($message, $extras, $data, $trace);
        
        return $eventId ?: false;
    }
    
    /**
     * Formats strings for when they're empty.
     * 
     * @param array $data
     * @param string $key
     * @return string
     */
    private function formatExtras($data, $key)
    {
        $key = strtolower($key);
        
        return empty($data[$key]) ? 'N/A' : $data[$key];
    }

}
