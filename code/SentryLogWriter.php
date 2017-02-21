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
 */

class SentryLogWriter extends \Zend_Log_Writer_Abstract
{
    
    /**
     * @const string
     */
    const SLW_NOOP = 'Unavailable';
    
    /**
     * @var ClientAdaptor
     */
    protected $client;
    
    /**
     * The constructor is usually called from factory().
     * 
     * @param string $env           Optionally pass a different Environment.
     * @param array $tags           Additional key=>value Tag pairs we may wish to report in
     *                              addition to that which is available by default in the
     *                              module and in Sentry itself.
     * @param array $extra          Pass arbitrary key>value pairs for display in
     *                              main Sentry UI.
     */
    public function __construct($env = null, array $tags = [], array $extra = [])
    {
        // Set default environment
        if (is_null($env)) {
            $env = \Director::get_environment_type();  
        }
               
        // Set all available user-data
        $userData = [];
        if ($member = \Member::currentUser()) {
            $userData = $this->defaultUserData($member);
        }
        
        // Set any available tags available in SS config
        $tags = array_merge($this->defaultTags(), $tags);
        
        $this->client = \Injector::inst()->createWithArgs('SentryClient', [
            $env, 
            $userData, 
            $tags, 
            $extra
        ]);
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
        $tags = isset($config['tags']) ? $config['tags'] : [];
        $extra = isset($config['extra']) ? $config['extra'] : [];
        
		return \Injector::inst()->createWithArgs('\SilverStripeSentry\SentryLogWriter', [
            $env, 
            $tags,
            $extra
        ]);
	}
    
    /**
     * Returns a default set of additional data specific to the user's part in
     * the request.
     * 
     * @param Member $member
     * @return array
     */
    public function defaultUserData(\Member $member)
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
    public function defaultTags()
    {
        return [
            'URL'           => $this->getURL(),
            'Request-Method'=> $this->getReqMethod(),
            'Request-Type'  => $this->getRequestType(),
            'Response-Code' => $this->getResponseCode(),
            'SAPI'          => $this->getSAPI(),
            'Controller'    => $this->getController(),
            'SS-version'    => $this->getPackageInfo('silverstripe/framework'),
            'Peak-Memory'   => $this->getMem()
        ];
    }
    
    /**
     * _write() forms the entry point into the physical sending of the error. The 
     * sending itself is done by the current client's `send()` method.
     * 
     * @param array $event  An array of data that is create din, and arrives here
     *                      via {@link SS_Log::log()}. 
     * @return void
     */
    protected function _write($event)
    {
        $message = $event['message']['errstr'];                 // From SS_Log::log()
        // The complete compliment of this data comes by use of the xxx_context() functions
        // in Raven_Client for example.
        $data = [
            'timestamp'     => strtotime($event['timestamp']),  // From ???
            'file'          => $event['message']['errfile'],    // From SS_Log::log()
            'line'          => $event['message']['errline'],    // From SS_Log::log()
            'context'       => $event['message']['errcontext'], // From SS_Log::log()
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
    public function getPackageInfo($package)
    {
        $return = 0;
        $cmd = sprintf('cd %s && composer info %s', BASE_PATH, $package);
        $output = [];
        $return = 0;
        
        exec($cmd, $output, $return);
        
        if ($return === 0 && count($output)) {
            $matches = [];
            preg_match_all("#versions\s:[^:]+#", implode('', $output), $matches);
            
            if(!empty($version = $matches[0][0])) {
                return trim(var_export($version, true));
            }
        }
        
        return self::SLW_NOOP;
    }
    
    /** 
     * Return the SilverStripe {@link Controller} of the relevant request.
     * 
     * @param boolean $rStr
     * @return mixed
     */
    public function getController($rStr = false)
    {
        if($controller = @\Controller::curr()) {
            return $rStr ? get_class($controller) : $controller;
        }
        
        return  $rStr ? self::SLW_NOOP : null;
    }
    
    /**
     * Return the IP address of the relevant request.
     * 
     * @return string
     */
    public function getIP()
    {
        if ($controller = $this->getController(false)) {
            return $controller->getRequest()->getIP();
        }
        
        return self::SLW_NOOP;
    }
    
    /**
     * What sort of request is this?
     * 
     * @return string
     */
    public function getRequestType()
    {
        return \Director::is_ajax() ? 'XMLHttpRequest' : 'Standard';
    }
    
    /**
     * Return the HTTP response code of the relevant request.
     * 
     * @return int
     */
    public function getResponseCode()
    {
        if ($controller = $this->getController(false)) {
            return $controller->getResponse()->getStatusCode();
        }
    }
    
    /**
     * Parse the User-Agent string to get O/S
     * 
     * @return string
     * @todo
     */
    public function getOS()
    {
        return self::SLW_NOOP;
    }
    
    /**
     * Return peak memory usage.
     * 
     * @return float
     */
    public function getMem()
    {
        $peak = memory_get_peak_usage(true) / 1024 / 1024;
        
        return (string) round($peak, 2) . 'Mb';
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
        
        return self::SLW_NOOP;
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
        
        return self::SLW_NOOP;
    }
    
    /**
     * 
     * @return string
     */
    public function getSAPI()
    {
        return php_sapi_name();
    }
    
    /**
     * Returns the URL at the time of error m(if any), including any GET params.
     * 
     * @return string
     */
    public function getURL()
    {
        if ($controller = $this->getController()) {
            if ($url = $controller->getRequest()->getURL(true)) {
                return $url;
            }
        }
        
        return self::SLW_NOOP;
    }
    
}
