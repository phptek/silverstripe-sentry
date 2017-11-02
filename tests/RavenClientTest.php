<?php

/**
 * Class: RavenClientTest.
 *
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PHPTek\Sentry\Test;

use PHPTek\Sentry\Log\SentryLogger;
use PHPTek\Sentry\Handler\SentryMonologHandler;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Config\Config;
use Monolog\Logger;

/**
 * Exercises RavenClient.
 */
class RavenClientTest extends SapphireTest
{

    /**
     * In the absence of fixture files, this is needed to force SaphhireTest into
     * creating us a test DB.
     *
     * @var boolean.
     */
    protected $usesDatabase = true;

    /**
     * Setup a dummy Sentry DSN so our errors are not actually sent
     * anywhere
     */
    public function setUpOnce()
    {
        parent::setUpOnce();

        Config::inst()->update(
            'PHPTek\Sentry\Adaptor\SentryClientAdaptor',
            'opts',
             ['dsn' => 'http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44']
        );
    }

    /**
     * Assert that the module's default tags make it through to the reporting process.
     *
     * @return void
     */
    public function testDefaultTagsAvailable()
    {        
        $logger = new Logger('error-log');
        $logger->pushHandler(new SentryMonologHandler());

        // Indirectly invoke SentryMonologHandler::write() via user_error()
        user_error('You have 30 seconds to reach minimum safe distance.');

        $tagsThatWereSet = $logger->getClient()->getData()['tags'];

        $this->assertArrayHasKey('Request-Method', $tagsThatWereSet);
        $this->assertArrayHasKey('Request-Type', $tagsThatWereSet);
        $this->assertArrayHasKey('SAPI', $tagsThatWereSet);
        $this->assertArrayHasKey('SS-Version', $tagsThatWereSet);
    }

    /**
     * Assert that custom "additional" (user) data makes it through to the
     * reporting process.
     *
     * @return void
     */
    public function testdefaultUserDataAvailable()
    {
        $logger = new Logger('error-log');
        $logger->pushHandler(new SentryMonologHandler());
        
        // Setup the "fixture data" for this test
        $this->logInWithPermission('admin');

        $userDataThatWasSet = $logger->getClient()->getData()['user'];

        // Cannot get Member data at by default at initialisation time
        $this->assertEquals('Unavailable', $userDataThatWasSet['ID']);
        $this->assertEquals('Unavailable', $userDataThatWasSet['Email']);
    }
    
    /**
     * Assert that custom "additional" (extra) data, (extras, tags env etc) makes
     * it through to the reporting process.
     *
     * @return void
     */
    public function testExtrasAvailable()
    {
        // Register SentryLogWriter with some custom context
        $fixture = [
            'extra' => [
                'foo' => 'bar'
            ],
            'env' => 'live'
        ];
        $logger = new Logger('error-log');
        $logger->pushHandler(new SentryMonologHandler(100, true, $fixture));

        // Invoke SentryLogWriter::_write()
        \SS_Log::log('You have 10 seconds to comply.', \SS_Log::ERR);

        $envThatWasSet = $logger->getClient()->getData()['env'];
        $xtraThatWasSet = $logger->getClient()->getData()['extra'];

        $this->assertEquals('live', $envThatWasSet);
        $this->assertArrayHasKey('foo', $xtraThatWasSet);
        $this->assertContains('bar', $xtraThatWasSet['foo']);

        // Cleanup
        \SS_Log::remove_writer($logger);
    }

}
