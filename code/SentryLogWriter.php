<?php

/**
 * Class: SentryLogWriter.
 *
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace phptek\Sentry;

require_once THIRDPARTY_PATH . '/Zend/Log/Writer/Abstract.php';

/**
 * The SentryLogWriter class simply acts as a bridge between the configured Sentry 
 * adaptor and SilverStripe's {@link SS_Log}.
 * 
 * Usage in your project's _config.php for example (See README for examples).
 *  
 *    SS_Log::add_writer(\phptek\Sentry\SentryLogWriter::factory(), '<=');
 */

class SentryLogWriter extends \Zend_Log_Writer_Abstract
{
    
    /**
     * Stipulates what gets shown in the Sentry UI, should some metric not be
     * available for any reason.
     *
     * @const string
     */
    const SLW_NOOP = 'Unavailable';
    
    /**
     * A static constructor as per {@link Zend_Log_FactoryInterface}.
     * 
     * @param  array $config    An array of optional additional configuration for
     *                          passing custom information to Sentry. See the README for more detail.
     * @return SentryLogWriter
     */
    public static function factory($config = [])
    {
        $env = isset($config['env']) ? $config['env'] : null;
        $tags = isset($config['tags']) ? $config['tags'] : [];
        $extra = isset($config['extra']) ? $config['extra'] : [];
        $writer = \Injector::inst()->get('SentryLogWriter');

        // Set default environment
        if (is_null($env)) {
            $env = $writer->defaultEnv();
        }

        // Set all available user-data
        $userData = $writer->defaultUserData();
        
        if ($member = \Member::currentUser()) {
            $userData = $writer->defaultUserData($member);
        }

        // Set any available tags available in SS config
        $tags = array_merge($writer->defaultTags(), $tags);

        // Set any avalable additional (extra) data
        $extra = array_merge($writer->defaultExtra(), $extra);

        $writer->client->setData('env', $env);
        $writer->client->setData('user', $userData);
        $writer->client->setData('tags', $tags);
        $writer->client->setData('extra', $extra);

        return $writer;
    }

    /**
     * Used in unit tests.
     *
     * @return SentryClientAdaptor
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Returns a default environment when one isn't passed to the factory()
     * method.
     *
     * @return string
     */
    public function defaultEnv()
    {
        return \Director::get_environment_type();
    }
    
    /**
     * Returns a default set of additional data specific to the user's part in
     * the request.
     * 
     * @param  Member $member
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
     * Returns a default set of additional "tags" we wish to send to Sentry.
     * By default, Sentry reports on several mertrics, and we're already sending 
     * {@link Member} data. But there are additional data that would be useful
     * for debugging via the Sentry UI.
     *
     * These data can augment that which is sent to Sentry at setup
     * time in _config.php. See the README for more detail.
     *
     * N.b. Tags can be used to group messages within the Sentry UI itself, so there
     * should only be "static" data being sent, not something that can drastically
     * or minutely change, such as memory usage for example.
     * 
     * @return array
     */
    public function defaultTags()
    {
        return [
            'Request-Method'=> $this->getReqMethod(),
            'Request-Type'  => $this->getRequestType(),
            'SAPI'          => $this->getSAPI(),
            'SS-Version'    => $this->getPackageInfo('silverstripe/framework')
        ];
    }

    /**
     * Returns a default set of extra data to show upon selecting a message for
     * analysis in the Sentry UI. This can augment the data sent to Sentry at setup
     * time in _config.php as well as at runtime when calling SS_Log itself.
     * See the README for more detail.
     *
     * @return array
     */
    public function defaultExtra()
    {
        return [
            'Peak-Memory'   => $this->getPeakMemory()
        ];
    }
    
    /**
     * _write() forms the entry point into the physical sending of the error. The 
     * sending itself is done by the current adaptor's `send()` method.
     * 
     * @param  array $event An array of data that is created in, and arrives here
     *                      via {@link SS_Log::log()} and {@link Zend_Log::log}.
     * @return void
     */
    protected function _write($event)
    {
        $message = $event['message']['errstr'];                             // From SS_Log::log()
        // The complete compliment of these data come via the Raven_Client::xxx_context() methods
        $data = [
            'timestamp' => strtotime($event['timestamp']),                  // From Zend_Log::log()
            'extra'     => isset($event['extra']) ? $event['extra'] : []    // From _config.php (Optional)
        ];
        $trace = \SS_Backtrace::filter_backtrace(debug_backtrace(), ['SentryLogWriter->_write', 'phptek\Sentry\SentryLogWriter->_write']);
        
        $this->client->send($message, [], $data, $trace);
    }
    
    /**
     * Return the version of $pkg taken from composer.lock.
     * 
     * @param  string $pkg e.g. "silverstripe/framework"
     * @return string
     */
    public function getPackageInfo($pkg)
    {
        $lockFileJSON = BASE_PATH . '/composer.lock';

        if (!file_exists($lockFileJSON) || !is_readable($lockFileJSON)) {
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
        $isCLI = $this->getSAPI() !== 'cli';
        $isAjax = \Director::is_ajax();

        return $isCLI && $isAjax ? 'AJAX' : 'Non-Ajax';
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
        $ua = @$_SERVER['HTTP_USER_AGENT'];
        
        if (!empty($ua)) {
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
        $method = @$_SERVER['REQUEST_METHOD'];
        
        if (!empty($method)) {
            return $method;
        }
        
        return self::SLW_NOOP;
    }
    
    /**
     * @return string
     */
    public function getSAPI()
    {
        return php_sapi_name();
    }
    
}
