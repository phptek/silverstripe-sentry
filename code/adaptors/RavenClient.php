<?php

namespace \Sentry\Adaptors\RavenClient;

use \Sentry\Adaptors\ClientAdaptor;

/**
 * The Sentry class simply acts as a bridge between the Raven PHP SDK and
 * SilverStripe itself.
 * 
 * @author Russell Michell 2017 <russ@theruss.com>
 * @package sentry
 * @todo Have a squiz at how the silverstripe-raygun module hooks into SS_Log
 */

class RavenClient extends ClientAdaptor
{    
    /**
     * Usage: 
     * 
     *  $sentry = Injector::inst()->create('Sentry');
     *  
     * Or via YML config
     * 
     * SentryClient:
     *   opts:
     *     dsn: 'https://123456@sentry.foo.bar/1234'
     * 
     * MyClass:
     *   sentry:
     *     Sentry
     * 
     * @param array $tags
     * @param array $userData
     * @return \Raven_Client
     */
    public function __construct(array $tags = [], array $userData = [])
    {        
        $dsn = $this->opts('dsn');
        $client = new Raven_Client($dsn);
        
        if (!is_null($env = $this->getEnv())) {
            $client->setEnvironment($env);
        }
        
        if ($user) {
            $client->user_context([
                'email' => $this->formatExtras($userData, 'email')
            ]);
        }
        
        if ($tags) {
            $this->setTags($tags);
        }
        
        if ($userData) {
            $this->setUserData($userData);
        }
        
        // Installs all available PHP error handlers
        if ($this->config()->install === true) {
            $client->install();
        }
        
        return $client;
    }
    
    /**
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
