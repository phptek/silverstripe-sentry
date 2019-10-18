<?php

/**
 * Class: SentryHandler.
 *
 * @author  Russell Michell 2017-2019 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Sentry\Severity;
use SilverStripe\Core\Injector\Injectable;
use PhpTek\Sentry\Log\SentryLogger;
use PhpTek\Sentry\Adaptor\SentryAdaptor;

/**
 * Monolog handler to send messages to a Sentry (https://github.com/getsentry/sentry) server
 * using sentry-php (https://github.com/getsentry/sentry-php).
 */
class SentryHandler extends AbstractProcessingHandler
{

    use Injectable;

    /**
     * @param  int     $level
     * @param  boolean $bubble
     * @param  array   $extras
     * @return void
     */
    public function __construct(int $level = Logger::DEBUG, bool $bubble = true, array $extras = [])
    {
        // Returns an instance of {@link SentryLogger}
        $logger = SentryLogger::factory($extras);
        $this->client = $logger->getAdaptor();

        parent::__construct($level, $bubble);
    }

    /**
     * write() forms the entry point into the physical sending of the error. The
     * sending itself is done by the current adaptor's `send()` method.
     *
     * @param  array $record An array of error-context metadata with the following
     *                       available keys:
     *
     *                       - message
     *                       - context
     *                       - level
     *                       - level_name
     *                       - channel
     *                       - datetime
     *                       - extra
     *                       - formatted
     *
     * @return void
     */
    protected function write(array $record) : void
    {
        $record = array_merge($record, [
            'timestamp' => $record['datetime']->getTimestamp(),
            'stacktrace' => SentryLogger::backtrace($record),
        ]);

        if (
                isset($record['context']['exception']) &&
                $record['context']['exception'] instanceof \Throwable
        ) {
            $this->client->getSDK()->captureException($record['context']['exception']);
        } else {
            $this->client->getSDK()->captureMessage($record['formatted'], new Severity(self::process_severity($record['level_name'])));
        }
    }

    /**
     * @return SentryAdaptor
     */
    public function getClient() : SentryAdaptor
    {
        return $this->client;
    }

    /**
     * Maps PHP's internal error-types into those suited to {@link Severity}.
     *
     * @param  mixed int|string $severity The incoming level from userland code or
     *                                    PHP itself.
     * @return string
     */
    private static function process_severity($severity) : string
    {
        // Stringified PHP severities out of \backtrace() like "notice"
        if (is_string($severity)) {
            $level = self::from_error($severity);
        // De-facto PHP severities as constants (ints) like E_NOTICE
        } else if (is_numeric($severity)) {
            $level = Severity::fromError($severity);
        } else {
            // "Other"
            $level = Severity::ERROR;
        }

        return strtolower($level);
    }

    /**
     * Almost an exact replica of {@link Severity::fromError()}, except we're
     * dealing with string values passed to us from upstream processes.
     * 
     * @param  string $severity An incoming severity.
     * @return string
     */
    private static function from_error(string $severity) : string
    {
        $severity = strtolower($severity);
        
        switch ($severity) {
            case 'deprecated':
            case 'user_deprecated':
            case 'warning':
            case 'user_warning':
                return Severity::WARNING;
            case 'error':
            case 'parse':
            case 'coreerror':
            case 'corwarning';
            case 'compilerrror':
            case 'compilewarning':
                return Severity::FATAL;
            case 'recoverablerror':
            case 'user_error':
                return Severity::ERROR;
            case 'notice':
            case 'user_notice':
            case 'strict':
                return Severity::INFO;
            default:
                // It's an error until proven otherwise
                return Severity::ERROR;
        }
    }

}
