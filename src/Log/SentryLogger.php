<?php

/**
 * Class: SentryLogger.
 *
 * @author  Russell Michell 2017-2019 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Log;

use SilverStripe\Control\Director;
use SilverStripe\Control\Middleware\TrustedProxyMiddleware;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Backtrace;
use SilverStripe\Security\Security;
use SilverStripe\Core\Config\Configurable;
use PhpTek\Sentry\Log\SentryLogger;
use PhpTek\Sentry\Adaptor\SentryAdaptor;

/**
 * The SentryLogWriter class is a bridge between {@link SentryAdaptor} and
 * SilverStripe's use of Monolog.
 */
class SentryLogger
{
    use Configurable;
    
    /**
     * @var SentryAdaptor
     */
    public $client = null;

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
     *                          passing custom information to Sentry. See the README
     *                          for more detail.
     * @return SentryLogger
     */
    public static function factory(array $config = []) : SentryLogger
    {
        $env = $config['env'] ?? [];
        $user = $config['user'] ?? [];
        $tags = $config['tags'] ?? [];
        $extra = $config['extra'] ?? [];
        // Set the minimum reporting level
        $level = $config['level'] ?? self::config()->get('log_level');
        $logger = Injector::inst()->create(static::class);

        // Set default environment
        $env = $env ?: $logger->defaultEnv();
        // Set any available user data
        $user = $user ?: $logger->defaultUser();
        // Set any available tags available in SS config
        $tags = array_merge($logger->defaultTags(), $tags);
        // Set any available additional (extra) data
        $extra = array_merge($logger->defaultExtra(), $extra);

        $logger->adaptor->setContext('env', $env);
        $logger->adaptor->setContext('tags', $tags);
        $logger->adaptor->setContext('extra', $extra);
        $logger->adaptor->setContext('user', $user);
        $logger->adaptor->setContext('level', $level);

        return $logger;
    }

    /**
     * @return SentryAdaptor
     */
    public function getAdaptor() : SentryAdaptor
    {
        return $this->adaptor;
    }

    /**
     * Returns a default environment when one isn't passed to the factory()
     * method.
     *
     * @return string
     */
    public function defaultEnv() : string
    {
        return Director::get_environment_type();
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
    public function defaultTags() : array
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
    public function defaultExtra() : array
    {
        return [
            'Peak-Memory'   => $this->getPeakMemory()
        ];
    }

    /**
     * Return the version of $pkg taken from composer.lock.
     *
     * @param  string $pkg e.g. "silverstripe/framework"
     * @return string
     */
    public function getPackageInfo(string $pkg) : string
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
     * What sort of request is this? (A harder question to answer than you might
     * think: http://stackoverflow.com/questions/6275363/what-is-the-correct-terminology-for-a-non-ajax-request)
     *
     * @return string
     */
    public function getRequestType() : string
    {
        $isCLI = $this->getSAPI() !== 'cli';
        $isAjax = Director::is_ajax();

        return $isCLI && $isAjax ? 'AJAX' : 'Non-Ajax';
    }

    /**
     * Return peak memory usage.
     *
     * @return string
     */
    public function getPeakMemory() : string
    {
        $peak = memory_get_peak_usage(true) / 1024 / 1024;

        return (string) round($peak, 2) . 'Mb';
    }

    /**
     * Basic User-Agent check and return.
     *
     * @return string
     */
    public function getUserAgent() : string
    {
        $ua = @$_SERVER['HTTP_USER_AGENT'];

        if (!empty($ua)) {
            return $ua;
        }

        return self::SLW_NOOP;
    }

    /**
     * Basic request method check and return.
     *
     * @return string
     */
    public function getReqMethod() : string
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
    public function getSAPI() : string
    {
        return php_sapi_name();
    }

 	/**
	 * Returns the client IP address which originated this request.
     * Lifted and modified from SilverStripe 3's SS_HTTPRequest.
	 *
	 * @return string
	 */
	public function getIP() : string
    {
		$headerOverrideIP = null;

		if (defined('TRUSTED_PROXY')) {
			$headers = (defined('SS_TRUSTED_PROXY_IP_HEADER')) ?
                [SS_TRUSTED_PROXY_IP_HEADER] :
                null;

			if(!$headers) {
				// Backwards compatible defaults
				$headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR'];
			}

			foreach($headers as $header) {
				if(!empty($_SERVER[$header])) {
					$headerOverrideIP = $_SERVER[$header];

					break;
				}
			}
		}

        $proxy = Injector::inst()->create(TrustedProxyMiddleware::class);

		if ($headerOverrideIP) {
			return $proxy->getIPFromHeaderValue($headerOverrideIP);
		}

        if (isset($_SERVER['REMOTE_ADDR'])) {
			return $_SERVER['REMOTE_ADDR'];
		}

        return '';
	}

    /**
     * Returns a default set of additional data specific to the user's part in
     * the request.
     *
     * @param  mixed Member|null $member
     * @return array
     */
    public function defaultUser(Member $member = null) : array
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        
        return [
            'IPAddress' => $this->getIP() ?: self::SLW_NOOP,
            'ID'       => $member ? $member->getField('ID') : self::SLW_NOOP,
            'Email'    => $member ? $member->getField('Email') : self::SLW_NOOP,
        ];
    }

    /**
     * Generate a cleaned-up backtrace of the event that got us here.
     *
     * @param  array $record
     * @return array
     * @todo   Unused in sentry-sdk 2.0??
     */
    public static function backtrace(array $record) : array
    {
        // Provided trace
        if (!empty($record['context']['trace'])) {
            return $record['context']['trace'];
        }

        // Generate trace from exception
        if (isset($record['context']['exception'])) {
            $exception = $record['context']['exception'];

            return $exception->getTrace();
        }

        // Failover: build custom trace
        $bt = debug_backtrace();

        // Push current line into context
        array_unshift($bt, [
            'file'     => !empty($bt['file']) ? $bt['file'] : 'N/A',
            'line'     => !empty($bt['line']) ? $bt['line'] : 'N/A',
            'function' => '',
            'class'    => '',
            'type'     => '',
            'args'     => [],
        ]);

       return Backtrace::filter_backtrace($bt, [
            '',
            'Monolog\\Handler\\AbstractProcessingHandler->handle',
            'Monolog\\Logger->addRecord',
            'Monolog\\Logger->log',
            'Monolog\\Logger->warn',
            'PhpTek\\Sentry\\Handler\\SentryMonologHandler->write',
            'PhpTek\\Sentry\\Handler\\SentryMonologHandler->backtrace',
        ]);
    }

}
