<?php

/**
 * Class: SentryLogWriterTest.
 *
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package phptek/sentry
 */

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;

/**
 * Excercises SentryLogWriter.
 */
class SentrHandlerTest extends SapphireTest
{
    /**
     * Setup test-specific context
     */
	public function setUpOnce()
    {
        parent::setUpOnce();

        // No idea why these need to be explicitly set. Although the suite runs,
        // we always see nest() / unnest() errors from phpunit..
        Injector::nest();
        Config::nest();

		\Phockito::include_hamcrest(true);

        // Setup a dummy Sentry DSN so our errors are not actually sent anywhere
        Config::inst()->update(
            'phptek\Sentry\Adaptor\SentryClientAdaptor',
            'opts',
            ['dsn' => 'http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44']
        );
	}

    /**
     * Simply tests that when SS_Log::log() is called, _write() is also called.
     *
     * @return void
     */
    public function testWriteIsCalled()
    {
        // Mock the SentryLogWriter
        $spy = \Phockito::spy('phptek\Sentry\SentryHandler');

        // Setup and invoke the logger
        $logger = Injector::inst()->get('Logger');
        $logger->pushHandler($spy);
        $logger->info('You have 30 seconds to reach minimum safe distance.');
        $handler = $logger->getHandlers()[0];
        $handler::factory();

        // Verificate
        \Phockito::verify($spy, 1)->write(arrayValue());
    }

}
