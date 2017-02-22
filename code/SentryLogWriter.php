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
        $userData = $this->defaultUserData();
        if ($member = \Member::currentUser()) {
            $userData = $this->defaultUserData($member);
        }
        
        // Set any available tags available in SS config
        $tags = array_merge($this->defaultTags(), $tags);
        
        $this->client = \Injector::inst()->createWithArgs('SentryClientAdaptor', [
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
     * Used mostly by unit tests.
     * 
     * @return ClientAdaptor 
     */
    public function getClient()
    {
        return $this->client;
    }
    
    /**
     * Returns a default set of additional data specific to the user's part in
     * the request.
     * 
     * @param Member $member
     * @return array
     */
    public function defaultUserData(\Member $member = null)
    {
        return [
            'IP-Address'    => $this->getIP(),
            'ID'            => $member ? $member->getField('ID') : self::SLW_NOOP,
            'Email'         => $member ? $member->getField('Email') : self::SLW_NOOP,
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
            'Request-Method'=> $this->getReqMethod(),
            'Request-Type'  => $this->getRequestType(),
            'SAPI'          => $this->getSAPI(),
            'SS-Version'    => $this->getPackageInfo('silverstripe/framework'),
            'Peak-Memory'   => $this->getPeakMemory()
        ];
    }
    
    /**
     * _write() forms the entry point into the physical sending of the error. The 
     * sending itself is done by the current client's `send()` method.
     * 
     * @param array $event  An array of data that is created in, and arrives here
     *                      via {@link SS_Log::log()}. 
     * @return void
     */
    protected function _write($event)
    {
        $message = $event['message']['errstr'];                 // From SS_Log::log()
        // The complete compliment of these data come via the Raven_Client::xxx_context() methods
        $data = [
            'timestamp' => strtotime($event['timestamp']),  // From ???
        ];
        $trace = \SS_Backtrace::filter_backtrace(debug_backtrace(), ['SentryLogWriter->_write']);
        
        $this->client->send($message, [], $data, $trace);
    }
    
    /**
     * Return the version of $pkg taken from composer.lock.
     * 
     * @param string $pkg e.g. "silverstripe/framework"
     * @return string
     */
    public function getPackageInfo($pkg)
    {
        $lockFileJSON = BASE_PATH . '/composer.lock';

        if (!file_exists($lockFileJSON)) {
            return self::SLW_NOOP;
        }

        $lockFileData = json_decode(file_get_contents($lockFileJSON), true);

        foreach ($lockFileData['packages'] as $package) {
            if ($package['name'] === $pkg) {
                return $package['version'];
            }
        }
        
        return self::SLW_NOOP;
    }
    
    /**
     * Return the IP address of the relevant request.
     * 
     * @return string
     */
    public function getIP()
    {
        $req = \Injector::inst()->create('SS_HTTPRequest', $this->getReqMethod(), '');
        if ($ip = $req->getIP()) {
            return $ip;
        }
        
        return self::SLW_NOOP;
    }
    
    /**
     * What sort of request is this? (A harder question to answer than you might
     * think: http://stackoverflow.com/questions/6275363/what-is-the-correct-terminology-for-a-non-ajax-request)
     * 
     * @return string
     */
    public function getRequestType()
    {
        return \Director::is_ajax() ? 'AJAX' : 'Non-Ajax';
    }
    
    /**
     * Return peak memory usage.
     * 
     * @return float
     */
    public function getPeakMemory()
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
    
}
