<?php

/**
 * Class: SentryMonologHandler.
 *
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Handler;

use Monolog\Logger;
use PhpTek\Sentry\Adaptor\RavenClient;
use PhpTek\Sentry\Adaptor\SentryClientAdaptor;
use PhpTek\Sentry\Log\SentryLogger;
use PhpTek\Sentry\Monolog\Handler\SentryRavenHandler;

/**
 * Monolog Handler for Sentry via Raven
 */
class SentryMonologHandler extends SentryRavenHandler
{
    /**
     * @var RavenClient
     */
    protected $client;

    /**
     * @param  int   $level
     * @param  bool  $bubble
     * @param  array $extras Extra parameters that will become "tags" in Sentry.
     * @return void
     */
    public function __construct($level = Logger::WARNING, $bubble = true, $extras = [])
    {
        // Returns an instance of {@link SentryLogger}
        $logger = SentryLogger::factory($extras);
        $sdk = $logger->getClient()->getSDK();
        $this->client = $logger->getClient();
        $this->client->setData('user', $this->getUserData(null, $logger));

        $log_level = \Config::inst()->get(self::class, 'log_level');
        $level = ($log_level) ? constant(Logger::class . '::'. $log_level) : $level;

        parent::__construct($sdk, $level, $bubble);
    }

    /**
     * @return SentryClientAdaptor
     */
    public function getClient()
    {
        return $this->client;
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
    protected function write(array $record)
    {
        $record = array_merge($record, [
            'timestamp'  => $record['datetime']->getTimestamp(),
            'stacktrace' => $this->backtrace($record),
        ]);

        // write() calls one of RavenHandler::captureException() or RavenHandler::captureMessage()
        // depending on if $record['context']['exception'] is an instance of Exception or not
        parent::write($record);
    }

    /**
     * Generate a cleaned-up backtrace of the event that got us here.
     *
     * @param  array $record
     * @return array
     */
    private function backtrace($record)
    {
        // Provided trace
        if (!empty($record['context']['trace'])) {
            return $record['context']['trace'];
        }

        // Generate trace from exception
        if (isset($record['context']['exception'])) {
            /** @var Exception $exception */
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

        $bt = \Backtrace::filter_backtrace($bt, [
            '',
            'Monolog\\Handler\\AbstractProcessingHandler->handle',
            'Monolog\\Logger->addRecord',
            'Monolog\\Logger->log',
            'Monolog\\Logger->warn',
            'PhpTek\\Sentry\\Handler\\SentryMonologHandler->write',
            'PhpTek\\Sentry\\Handler\\SentryMonologHandler->backtrace',
        ]);

        return $bt;
    }

    /**
     * Returns a default set of additional data specific to the user's part in
     * the request.
     *
     * @param  Member       $member
     * @param  SentryLogger $logger
     * @return array
     */
    private function getUserData(\Member $member = null, $logger)
    {
        if (!$member) {
            $member = \Security::getCurrentUser();
        }

        return [
            'IPddress' => $logger->getIP(),
            'ID'       => $member ? $member->getField('ID') : SentryLogger::SLW_NOOP,
            'Email'    => $member ? $member->getField('Email') : SentryLogger::SLW_NOOP,
        ];
    }
}
