<?php

namespace SilverStripeSentry\Adaptors;

/**
 * Adaptor base class.
 * 
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package silverstripe/sentry
 */

abstract class SentryClientAdaptor extends \Object
{
    /**
     * @param string
     */
    protected $env;
    
    /**
     * @var array
     */
    protected $tags;
    
    /**
     * @var array
     */
    protected $userData;
    
    /**
     * @var array
     */
    protected $extra;
    
    /**
     * @param mixed $opt
     * @return mixed
     */
    protected function getOpts($opt)
    {
        $opts = self::config()->opts;
        
        if (!is_null($opts) && !empty($opts[$opt])) {
            return $opts[$opt];
        }
        
        return null;
    }
    
    /**
     * @return string
     */
    abstract public function getLevel($level);
    
    /**
     * @return string
     */
    public function getEnv()
    {
        return $this->env;
    }
    
    /**
     * @return array
     */
    public function getTags($tags)
    {
        return $this->tags;
    }
    
    /**
     * @return array
     */
    public function getUserData()
    {
        return $this->userData;
    }
    
    /**
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }
    
}
