<?php

/**
 * Class: SentryLogger.
 *
 * @author  Russell Michell 2017-2024 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Log;

use Composer\InstalledVersions;
use Monolog\Logger;
use Sentry\Frame;
use Sentry\Client;
use SilverStripe\Control\Director;
use SilverStripe\Dev\Backtrace;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Core\Config\Configurable;
use PhpTek\Sentry\Adaptor\SentryAdaptor;
use SilverStripe\Control\Controller;

/**
 * The SentryLogger class is a bridge between {@link SentryAdaptor} and
 * SilverStripe's use of Monolog.
 */
class SentryLogger
{
    use Configurable;

    /**
     * @var string
     */
    public const DEFAULT_IP = '0.0.0.0';

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
     * @param  Client $client
     * @param  array $config    An array of optional additional configuration for
     *                          passing custom information to Sentry. See the README
     *                          for more detail.
     * @return SentryLogger
     */
    public static function factory(Client $client, array $config = []): SentryLogger
    {
        $logger = new static();
        $env = $logger->defaultEnv();
        $tags = $logger->defaultTags();
        $extra = $logger->defaultExtra();
        $traces_sample_rate = $logger->defaultTracesSample();

        $user = [];
        $level = Logger::DEBUG;

        if ($config) {
            $env = $config['env'] ?? $env;
            $tags = array_merge($tags, $config['tags'] ?? []);
            $extra = array_merge($extra, $config['extra'] ?? []);
            $user = $config['user'] ?? $user;
            $level = $config['level'] ?? $level;
            $traces_sample_rate = $config['traces_sample_rate'] ?? $traces_sample_rate;
        }

        $adaptor = (new SentryAdaptor($client))
            ->setContext('env', $env)
            ->setContext('tags', $tags)
            ->setContext('extra', $extra)
            ->setContext('user', $user)
            ->setContext('traces_sample_rate', $traces_sample_rate);


        $set_adaptor = $logger->setAdaptor($adaptor);

        // trigger the performance tracing if set
        if ($traces_sample_rate) {
            $set_adaptor->getAdaptor()->startTransaction();
        }

        return $set_adaptor;
    }

    /**
     * @return SentryAdaptor
     */
    public function getAdaptor(): SentryAdaptor
    {
        return $this->adaptor;
    }

    /**
     * @param  SentryAdaptor $adaptor
     * @return SentryLogger
     */
    public function setAdaptor(SentryAdaptor $adaptor): SentryLogger
    {
        $this->adaptor = $adaptor;

        return $this;
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
            'request.method' => self::get_req_method(),
            'request.type' => self::get_req_type(),
            'php.sapi' => self::get_sapi(),
            'silverstripe.framework.version' => self::get_package_info('silverstripe/framework'),
            'phptek.sentry.version' => self::get_package_info('phptek/sentry'),
            'app' => self::get_app_info(),
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
            'PHP Peak Memory' => self::get_peak_memory(),
        ];
    }

    /**
     * Returns a default empty array for traces_sample_rate, as this should be blank if not set manually
     *
     * @return array
     */
    public function defaultTracesSample(): array
    {
        return [];
    }

    /**
     * What sort of request is this? (A harder question to answer than you might
     * think: http://stackoverflow.com/questions/6275363/what-is-the-correct-terminology-for-a-non-ajax-request)
     *
     * @return string
     */
    public static function get_req_type(): string
    {
        $isCLI = self::get_sapi() !== 'cli';
        $isAjax = Director::is_ajax();

        return $isCLI && $isAjax ? 'AJAX' : 'Non-Ajax';
    }

    /**
     * Return peak memory usage.
     *
     * @return string
     */
    public static function get_peak_memory(): string
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
    public static function get_req_method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? self::SLW_NOOP;
    }

    /**
     * @return string
     */
    public static function get_sapi(): string
    {
        return php_sapi_name();
    }

    /**
     * @param  string $package
     * @return string
     */
    public static function get_package_info(string $package): string
    {
        return InstalledVersions::getVersion(trim($package)) ?? self::SLW_NOOP;
    }

    /**
     * Format and return a string of metadata about the app in which this module is installed.
     *
     * @return string
     */
    public static function get_app_info(): string
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
     * @return mixed null|string
     */
    public static function get_ip(): ?string
    {
        if (Controller::has_curr()) {
            $controller = Controller::curr();

            if ($request = $controller->getRequest()) {
                return $request->getIP();
            }
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return null;
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
            'ip_address' => self::get_ip() ?? self::DEFAULT_IP,
            'id'       => $member ? $member->getField('ID') : self::SLW_NOOP,
            'email'    => $member ? $member->getField('Email') : self::SLW_NOOP,
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

        return array_map(function ($item) {
            return new Frame(
                $item['function'] ?? self::DEFAULT_FRAME_VAL,
                $item['file'] ?? self::DEFAULT_FRAME_VAL,
                $item['line'] ?? 0,
                $item['raw_function'] ?? self::DEFAULT_FRAME_VAL,
                $item['abs_filepath'] ?? self::DEFAULT_FRAME_VAL,
                $item['vars'] ?? [],
                $item['in_app'] ?? true
            );
        }, $filtered);
    }
}
