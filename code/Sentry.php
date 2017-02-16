<?php

namespace \Sentry\Sentry;

/**
 * The Sentry class simply acts as a bridge between the configured Sentry adaptor
 * and SilverStripe itself.
 * 
 * @author Russell Michell 2017 <russ@theruss.com>
 * @package sentry
 * @todo Hook into SilverStripe's logging pipeline via SS_Log
 */

class Sentry
{

    /**
     * 
     * @param string $env
     */
    public function __construct($env = null)
    {
        // Set environment
        if (is_null($env)) {
            $env = Director::get_environment_type();  
        }
        
        $this->client->setEnv($env);
        
        // Set any available user-data
        if ($member = Member::currentUser()) {
            $this->client->setUserData($member->toMap());
        }
        
        // Set any available tags available in SS config
        if ($tags = $this->config()->tags) {
            $this->client->setTags(array_merge(
                $this->client->getTags(), // Fetch tags set in config or baked-in
                $tags
            ));
        }
        
    }
}
