<?php

/**
 * Class: RavenClientTest.
 *
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package phptek/sentry
 */

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use Monolog\Logger;

/**
 * Excercises RavenClient.
 *
 * @todo Ensure we test in all of SS4's environment modes
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
            'phptek\Sentry\Adaptor\SentryClientAdaptor',
            'opts',
             ['dsn' => 'http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44']
        );
        
        // Register SentryLogWriter with some custom context
        $fixture = [
            'extra' => [
                'foo' => 'bar'
            ],
            'env' => 'live'
        ];
        
        Config::inst()->update(
            'SentryHandler',
            'constructor',
            $fixture
        );
        
    }

    /**
     * Assert that the module's default tags make it through to the reporting process.
     *
     * @return void
     */
    public function testDefaultTagsAvailable()
    {
        // Setup and invoke the logger
        $logger = Injector::inst()->get('Logger');
        $logger->log(Logger::INFO, 'You have 30 seconds to reach minimum safe distance.');
        $handler = $logger->getHandlers()[0]; // Is there a batter way to do this?

        $ravenSDKClient = $handler->getClient()->getSDK();
        $tagsThatWereSet = $ravenSDKClient->context->tags;

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
        // Setup the "fixture data" for this test
        $_SERVER['REMOTE_ADDR'] = '192.168.1.2';
        $this->logInWithPermission('admin');

        // Setup and invoke the logger
        $logger = Injector::inst()->get('Logger');
        $logger->log(Logger::INFO, 'You have 10 seconds to comply.'); // Use INFO so as to not print exception to stdout
        $handler = $logger->getHandlers()[0]; // Is there a batter way to do this?

        $ravenSDKClient = $handler->getClient()->getSDK();
        $userDataThatWasSet = $ravenSDKClient->context->user;

        $this->assertArrayHasKey('IP-Address', $userDataThatWasSet);
        $this->assertArrayHasKey('ID', $userDataThatWasSet);
        $this->assertArrayHasKey('Email', $userDataThatWasSet);

        $this->assertEquals('192.168.1.2', $userDataThatWasSet['IP-Address']);
        $this->assertEquals(1, $userDataThatWasSet['ID']);
        $this->assertEquals('admin@example.org', $userDataThatWasSet['Email']);
    }
    
    /**
     * Assert that custom "additional" (extra) data, (extras, tags env etc) makes
     * it through to the reporting process.
     *
     * @return void
     */
    public function testExtrasAvailable()
    {

        // Setup and invoke the logger
        $logger = Injector::inst()->get('Logger');
        $logger->log(Logger::INFO, 'Nuke it from orbit.'); // Use INFO so as to not print exception to stdout
        $handler = $logger->getHandlers()[0]; // Is there a batter way to do this?

        $ravenSDKClient = $handler->getClient()->getSDK();
        $envThatWasSet = $ravenSDKClient->getEnvironment();
        $xtraThatWasSet = $ravenSDKClient->context->extra;

        $this->assertEquals('live', $envThatWasSet);
        $this->assertArrayHasKey('foo', $xtraThatWasSet);
        $this->assertContains('bar', $xtraThatWasSet['foo']);
        
        Config::unnest();
        
    }

}
