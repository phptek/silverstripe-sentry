<?php

namespace SilverStripeSentry;

require_once THIRDPARTY_PATH . '/Zend/Log/Writer/Abstract.php';

/**
 * The SentryLogWriter class simply acts as a bridge between the configured Sentry 
 * adaptor and SilverStripe's {@link SS_Log}.
 * 
 * Usage in your project's _config.php for example:
 *  
 *    SS_Log::add_writer(SentryLogWriter::factory(), '<=');
 * 
 * @author Russell Michell 2017 <russ@theruss.com>
 * @package silverstripe/sentry
 * @todo Ensure the following are reported:
 *  - The URL at which the error occurred
 *  - [DONE] The log-level taken from SS_Log
 *  - [DONE] Any available logged-in member data
 *  - [DONE] The environment
 *  - [DONE] Arbitrary additional "tags" as key=>value pairs, passed at call time
 */

class SentryLogWriter extends \Zend_Log_Writer_Abstract
{
    
    /**
     * @var ClientAdaptor
     */
    protected $client;
    
    /**
     * The constructor is usually called from factory().
     * 
     * @param string $env   Optionally pass a different environment.
     * @param array $tags   Additional key=>value pairs we may wish to report in
     *                      addition to that which is available by default in the
     *                      module and in Sentry itself.
     */
    public function __construct($env = null, array $tags = null)
    {
        // Set default environment
        if (is_null($env)) {
            $env = \Director::get_environment_type();  
        }
        
        $this->client = \Injector::inst()->create('SentryClient');
        
        $this->client->setEnv($env);
        
        // Set all available user-data
        if ($member = \Member::currentUser()) {
            $this->client->setUserData($this->user($member));
        }
        
        // Set any available tags available in SS config
        if (!is_null($tags)) {
            $this->client->setTags(array_merge(
                $this->tags(),
                $tags
            ));
        }
    }
    
    /**
     * For flexibility, the factory should be the usual entry point into this class,
     * but there's no reason the constructor can't be called directly if for example, only
     * local errror-reporting is required.
     * 
     * @param array $config
     * @return SentryLogWriter
     */
	public static function factory($config = [])
    {
        $env = isset($config['env']) ? $config['env'] : null;
        $tags = isset($config['tags']) ? $config['tags'] : null;
        
		return \Injector::inst()->create('\SilverStripeSentry\SentryLogWriter', $env, $tags);
	}
    
    /**
     * Returns a default set of additional data specific to the user's part in
     * the request.
     * 
     * @param Member $member
     * @return array
     */
    public function user(\Member $member)
    {
        return [
            'ip_address'    => $this->getIP(),
            'id'            => $member->getField('ID'),
            'email'         => $member->getField('Email'),
            'os'            => $this->getOS(),
            'user_agent'    => $this->getUserAgent()
        ];
    }
    
    /**
     * Returns a default set of additional tags we wish to send to Sentry.
     * By default, Sentry reports on several mertrics, and we're already sending 
     * {@link Member} data. But there are additional data that would be useful
     * for debugging via the Sentry UI.
     * 
     * @return array
     */
    public function tags()
    {
        return [
            'URL'           => $this->getURL(),
            'Request-Method'=> $this->getReqMethod(),
            'Request-Type'  => $this->requestType(),
            'SAPI'          => php_sapi_name(),
            'controller'    => $this->getController(),
            'SS-version'    => $this->composerInfo('silverstripe/framework'),
            'Peak-Memory'   => $this->getMem()
        ];
    }
    
    /**
     * @param array $event  An array of data that is create din, and arrives here
     *                      via {@link SS_Log::log()}. 
     * @return void
     */
    protected function _write($event)
    {
        $message = $event['message']['errstr'];             // From SS_Log::log()
        $data = [
            'level'     => $this->client->level($event['priorityName']),
            'timestamp' => strtotime($event['timestamp']),  // From ???
            'file'      => $event['message']['errfile'],    // From SS_Log::log()
            'line'      => $event['message']['errline'],    // From SS_Log::log()
            'context'   => $event['message']['errcontext'], // From SS_Log::log()
            'tags'      => $this->client->getTags()
        ];
        $trace = \SS_Backtrace::filter_backtrace(debug_backtrace(), ['SentryLogWriter->_write']);
        
        $this->client->send($message, [], $data, $trace);
    }
    
    /**
     * Return a formatted result of running the "composer info" command over an
     * optional $package.
     * 
     * @param string $package
     * @return string
     */
    public function composerInfo($package)
    {
        $return = 0;
        $cmd = sprintf('cd %s && composer info %s', BASE_PATH, $package);
        $result = passthru($cmd, $return);
        
        if ($return === 0 && strlen($result)) {
            $parts = explode(' ', $result);
            $version = end($parts);
            
            return var_export($version, true);
        }
        
        return 'Unavailable';
    }
    
    /** 
     * Returns the name of the current controller.
     * 
     * @return string
     */
    public function getController()
    {
        if($curr = @Controller::curr()) {
            return $curr;
        }
        
        return 'Unavailable';
    }
    
    /**
     * @return string
     */
    public function getIP()
    {
        if ($controller = $this->getController()) {
            return $controller->getRequest()->getIP();
        }
        
        return 'Unavailable';
    }
    
    /**
     * What sort of request is this?
     * 
     * @return string
     */
    public function requestType()
    {
        return \Director::is_ajax() ? 'XMLHttpRequest' : 'Standard';
    }
    
    /**
     * Parse the User-Agent string to get O/S
     * 
     * @return string
     * @todo
     */
    public function getOS()
    {
        return 'Unavailable';
    }
    
    /**
     * Return peak memory usage.
     * 
     * @return float
     */
    public function getMem()
    {
        $peak = memory_get_peak_usage(true) / 1024 / 1024;
        
        return round($peak, 2) . 'Mb';
    }
    
    /**
     * Basic User-Agent check and return.
     * 
     * @return string
     */
    public function getUserAgent()
    {
        if (!empty($ua = @$_SERVER['HTTP_USER_AGENT'])) {
            return $ua;
        }
        
        return 'Unavailable';
    }
    
    /**
     * Basic reuqest method check and return.
     * 
     * @return string
     */
    public function getReqMethod()
    {
        if (!empty($method = @$_SERVER['REQUEST_METHOD'])) {
            return $method;
        }
        
        return 'Unavailable';
    }
    
    /**
     * Returns the frontend URL at the time of error, including any GET params.
     * 
     * @return string
     */
    public function getURL()
    {
        if ($controller = $this->getController()) {
            return $controller->getRequest()->getURL(true);
        }
        
        return 'Unavailable';
    }
    
}
