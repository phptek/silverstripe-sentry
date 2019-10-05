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
     * @return SentryAdaptor
     */
    public function getClient() : SentryAdaptor
    {
        return $this->client;
    }
}
