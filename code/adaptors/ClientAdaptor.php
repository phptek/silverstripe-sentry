<?php

namespace SilverStripeSentry\Adaptors;

/**
 * Adaptor base class.
 * 
 * @author Russell Michell 2017 <russ@theruss.com>
 * @package silverstripe/sentry
 */

abstract class ClientAdaptor extends \Object
{
    /**
     * 
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
     * @return string
     */
    abstract public function level($level);
    
    /**
     * 
     * @param mixed $opt
     * @return mixed
     */
    protected function opts()
    {
        $opts = $this->config()->opts;
        
        if (!is_null($opts) && !empty($opts[$opt])) {
            return $opts[$opt];
        }
        
        return $opts;
    }
    
    /**
     * 
     * @param string $env
     */
    public function setEnv($env)
    {
        $this->env = $env;
    }
    
    /**
     * 
     * @return string
     */
    public function getEnv()
    {
        return $this->env;
    }
    
    public function setUserData($data)
    {
        $this->userData = $data;
    }
    
    /**
     * 
     * @return array
     */
    public function getUserData()
    {
        return $this->userData;
    }
    
    public function setTags($tags)
    {
        $this->tags = $tags;
    }
    
    /**
     * 
     * @return array
     */
    public function getTags($tags)
    {
        return $this->tags;
    }
    
    /**
     * Allows adaptor instances to pass additional, arbitrary data to Sentry.
     * 
     * @param array $extra
     * @return ClientAdaptor
     */
    public function addExtraData(array $data = [])
    {
        return $this;
    }
    
}
