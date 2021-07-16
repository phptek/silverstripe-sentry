<?php

/**
 * Class: SentryHandler.
 *
 * @author  Russell Michell 2017-2021 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Handler;

use Throwable;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use PhpTek\Sentry\Adaptor\SentryAdaptor;
use Sentry\Severity;
use Sentry\EventHint;
use Sentry\Stacktrace;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Sentry\ClientBuilder;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Security\Security;
use PhpTek\Sentry\Log\SentryLogger;
use PhpTek\Sentry\Adaptor\SentrySeverity;

/**
 * Monolog handler to send messages to a Sentry (https://github.com/getsentry/sentry) server
 * using sentry-php (https://github.com/getsentry/sentry-php).
 */
class SentryHandler extends AbstractProcessingHandler
{
    use Injectable;

    /**
     * @var mixed int|null
     */
    private static $log_level = null;

    /**
     * @var mixed SentryLogger|null
     */
    private $logger = null;

    /**
     * @param  int     $level
     * @param  boolean $bubble
     * @param  array   $config
     * @return void
     */
    public function __construct($level = null, bool $bubble = true, array $config = [])
    {
        $client = ClientBuilder::create([])->getClient();
        // ^^ THIS Commented: x1 error displayed. None sent to Sentry
        //$level = $level ?: $this->config()->get('log_level');
        //$level = Logger::getLevels()[$level] ?? Logger::DEBUG;
        $level = 100;

        SentrySdk::setCurrentHub(new Hub($client));
        // ^^ THIS Un-Commented AND $client Uncommented x2 error displayed. None sent to Sentry

        //$this->logger = SentryLogger::factory($config, $client);
        //$this->client = $client;

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
    protected function write(array $record)
    {
        return null;
        $record = array_merge($record, [
            'timestamp' => $record['datetime']->getTimestamp(),
        ]);
        $adaptor = $this->logger->getAdaptor();

        // For reasons..this is the only spot where we're able to getCurrentUser()
        $adaptor->setContext('user', SentryLogger::user_data(Security::getCurrentUser()));

        // Create a Sentry EventHint and pass an instance of Stacktrace to it.
        // See SentryAdaptor: We explicitly enable/disable default (Sentry) stacktraces.
        if ($adaptor::get_opts('custom_stacktrace')) {
            $eventHint = EventHint::fromArray([
                'stacktrace' => new Stacktrace(SentryLogger::backtrace($record)),
            ]);
        }

        if (
                isset($record['context']['exception']) &&
                $record['context']['exception'] instanceof Throwable
        ) {
            $this->client->captureException(
                $record['context']['exception'],
                $adaptor->getContext(),
                isset($eventHint) ? $eventHint : null
            );
        } else {
            $this->client->captureMessage(
                $record['formatted'],
                new Severity(SentrySeverity::process_severity($record['level_name'])),
                $adaptor->getContext(),
                isset($eventHint) ? $eventHint : null
            );
        }
    }

}
