<?php

/**
 * Class: SentryLogger.
 *
 * @author  Russell Michell 2017-2021 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Log;

use Composer\InstalledVersions;
use Monolog\Logger;
use Sentry\Frame;
use SilverStripe\Control\Director;
use SilverStripe\Control\Middleware\TrustedProxyMiddleware;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Backtrace;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Core\Config\Configurable;
use PhpTek\Sentry\Adaptor\SentryAdaptor;

/**
 * The SentryLogger class is a bridge between {@link SentryAdaptor} and
 * SilverStripe's use of Monolog.
 */
class SentryLogger
{
    use Configurable;

    /**
     * @var SentryAdaptor
     */
    public $adaptor = null;

    /**
     * Stipulates what gets shown in the Sentry UI, should some metric not be
     * available for any reason.
     *
     * @var string
     */
    const SLW_NOOP = 'Unavailable';

    /**
     * Default text to show if in self-generated stacktraces, we're unable to discern data.
     *
     * @var string
     */
    public const DEFAULT_FRAME_VAL = 'Unknown';

    /**
     * Factory, consumed by {@link SentryHandler}.
     *
     * @param  array $config    An array of optional additional configuration for
     *                          passing custom information to Sentry. See the README
     *                          for more detail.
     * @return SentryLogger
     */
    public static function factory(array $config = []): SentryLogger
    {
        $logger = new static();
        $env = $logger->defaultEnv();
        $tags = $logger->defaultTags();
        $extra = $logger->defaultExtra();
        $user = [];
        $level = Logger::DEBUG;

        if ($config) {
            $env = $config['env'] ?? $level;
            $tags = array_merge($tags, $config['tags'] ?? []);
            $extra = array_merge($extra, $config['extra'] ?? []);
            $user = $config['user'] ?? $user;
            $level = $config['level'] ?? $level;
        }

        $logger->adaptor = new SentryAdaptor();

        // $logger->adaptor->setContext('env', $env);
        // $logger->adaptor->setContext('tags', $tags);
        // $logger->adaptor->setContext('extra', $extra);
        // $logger->adaptor->setContext('user', $user);


        return $logger;
    }

    /**
     * Returns a default environment when one isn't passed to the factory()
     * method.
     *
     * @return string
     */
    public function defaultEnv(): string
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
    public function defaultTags(): array
    {
        return [
            'request.method'=> $this->getReqMethod(),
            'request.type' => $this->getRequestType(),
            'php.sapi' => $this->getSAPI(),
            'silverstripe.framework.version' => $this->getPackageInfo('silverstripe/framework'),
            'phptek.sentry.version' => $this->getPackageInfo('phptek/sentry'),
            'app' => $this->getAppInfo(),
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
    public function defaultExtra(): array
    {
        return [
            'php.peak.memory' => $this->getPeakMemory(),
        ];
    }

    /**
     * What sort of request is this? (A harder question to answer than you might
     * think: http://stackoverflow.com/questions/6275363/what-is-the-correct-terminology-for-a-non-ajax-request)
     *
     * @return string
     */
    public function getRequestType(): string
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
    public function getPeakMemory(): string
    {
        $peak = memory_get_peak_usage(true) / 1024 / 1024;

        return (string) round($peak, 2) . 'Mb';
    }

    /**
     * Basic User-Agent check and return.
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? self::SLW_NOOP;
    }

    /**
     * Basic request method check and return.
     *
     * @return string
     */
    public function getReqMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? self::SLW_NOOP;
    }

    /**
     * @return string
     */
    public function getSAPI(): string
    {
        return php_sapi_name();
    }

    /**
     * @param  string $package
     * @return string
     */
    public function getPackageInfo(string $package): string
    {
        return InstalledVersions::getVersion(trim($package)) ?? self::SLW_NOOP;
    }

    /**
     * Format and return a string of metadata about the app in which this module is installed.
     *
     * @return string
     */
    private function getAppInfo(): string
    {
        $meta = InstalledVersions::getRootPackage();
        $data = [
            'project' => $meta['name'] ?? self::SLW_NOOP,
            'branch' => $meta['version'] ?? self::SLW_NOOP,
            'commit' => $meta['reference'] ?? self::SLW_NOOP,
        ];

        // If we have nothing to show, then show a meaningful default
        $filtered = array_filter($data, function ($item): bool {
            return $item == self::SLW_NOOP;
        });

        if (count($filtered) === count($data)) {
            return self::SLW_NOOP;
        }

        return sprintf('%s: %s (%s)', $data['project'], $data['branch'], $data['commit']);
    }

    /**
     * Returns the client IP address which originated this request.
     * Lifted and modified from SilverStripe 3's SS_HTTPRequest.
     *
     * @return string
     */
    public static function get_ip(): string
    {
        $headerOverrideIP = null;

        if (defined('TRUSTED_PROXY')) {
            $headers = (defined('SS_TRUSTED_PROXY_IP_HEADER')) ?
                [SS_TRUSTED_PROXY_IP_HEADER] :
                null;

            if (!$headers) {
                // Backwards compatible defaults
                $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR'];
            }

            foreach ($headers as $header) {
                if (!empty($_SERVER[$header])) {
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
    public static function user_data(Member $member = null): array
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        return [
            'IPAddress' => self::get_ip() ?: self::SLW_NOOP,
            'ID'       => $member ? $member->getField('ID') : self::SLW_NOOP,
            'Email'    => $member ? $member->getField('Email') : self::SLW_NOOP,
        ];
    }

    /**
     * Manually extract or generate a suitable backtrace. This is especially useful
     * in non-exception reports such as those that use Sentry\Client::captureMessage().
     *
     * Note: Calling logic only makes use of this if the "custom_stacktrace" option is enabled.
     * As it is, it requires a little more work to make it on-par with Sentry's defaults (which is this
     * module's default also).
     *
     * @param  array $record
     * @return array An array of filtered Sentry\Frame objects.
     */
    public static function backtrace(array $record): array
    {
        if (!empty($record['context']['trace'])) {
            // Provided trace
            $bt = $record['context']['trace'];
        } else if (isset($record['context']['exception'])) {
            // Generate trace from exception
            $bt = $record['context']['exception']->getTrace();
        } else {
            // Failover: build custom trace
            $bt = debug_backtrace();

            // Push current line into context
            array_unshift($bt, [
                'file'     => $bt['file'] ?? self::DEFAULT_FRAME_VAL,
                'line'     => (int) ($bt['line'] ?? 0),
                'function' => '',
                'class'    => '',
                'type'     => '',
                'args'     => [],
            ]);
        }

        // Regardless of where it came from, filter the exception
        $filtered = Backtrace::filter_backtrace($bt, [
            '',
            'Monolog\\Handler\\AbstractProcessingHandler->handle',
            'Monolog\\Logger->addRecord',
            'Monolog\\Logger->error',
            'Monolog\\Logger->log',
            'Monolog\\Logger->warn',
            'PhpTek\\Sentry\\Handler\\SentryHandler',
            'PhpTek\\Sentry\\Handler\\SentryHandler::write',
            'PhpTek\\Sentry\\Logger\\SentryLogger::backtrace',
        ]);

        return array_map(function($item) {
            return new Frame(
                $item['function'] ?? self::DEFAULT_FRAME_VAL,
                $item['file'] ?? self::DEFAULT_FRAME_VAL,
                $item['line'] ?? 0,
                $item['raw_function'] ?? self::DEFAULT_FRAME_VAL,
                $item['abs_filepath'] ?? self::DEFAULT_FRAME_VAL,
                $item['vars'] ?? [],
                $item['in_app'] ?? true,
            );
        }, $filtered);
    }

}
