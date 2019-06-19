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

/**
 * Handler to send messages to a Sentry (https://github.com/getsentry/sentry) server
 * using sentry-php (https://github.com/getsentry/sentry-php)
 *
 * This is the entry point for error handling in SilverStripe. See _config/config.yml
 * for how we push the handler onto the stack.
 */
class SentryHandler extends AbstractProcessingHandler
{
    use Injectable;
    
    /**
     * A mapping of log-level values between Zend_Log => Raven_Client
     *
     * @var array
     */
    protected $logLevels = [
        'NOTICE' => Severity::INFO,
        'WARN'   => Severity::WARNING,
        'ERR'    => Severity::ERROR,
        'EMERG'  => Severity::FATAL
    ];

    /**
     * @param  int     $level
     * @param  boolean $bubble
     * @param  array   $extras
     * @return void
     */
    public function __construct($level = Logger::DEBUG, $bubble = true, array $extras = [])
    {
        // Returns an instance of {@link SentryLogger}
        $logger = SentryLogger::factory($extras);
        $this->client = $logger->getAdaptor();

        parent::__construct($this->client->getSDK(), $level, $bubble);
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
            'timestamp'  => $record['datetime']->getTimestamp(),
            'stacktrace' => SentryLogger::backtrace($record),
        ]);

        if (
                isset($record['context']['exception']) &&
                $record['context']['exception'] instanceof \Throwable
            ) {
            $this->client->getSDK()->captureException($record['context']['exception']);
        } else {
            $this->client->getSDK()->captureMessage($record['formatted'], new Severity(strtolower($record['level_name'])));
        }
    }
    
    /**
     * @return
     */
    public function getClient()
    {
        return $this->client;
    }
}
