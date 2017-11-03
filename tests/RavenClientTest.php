<?php

/**
 * Class: RavenClientTest.
 *
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package phptek/sentry
 */

use PhpTek\Sentry\Handler\SentryMonologHandler,
    SilverStripe\Dev\SapphireTest,
    SilverStripe\Core\Config\Config,
    Monolog\Logger;

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
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        Config::modify()->set(
            'PhpTek\Sentry\Adaptor\SentryClientAdaptor',
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
        $handler = $logger->getHandlers()[0];
        $tagsThatWereSet = $handler->getClient()->getData()['tags'];

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
     * @todo Need to mock a SentryMonologHandler
     */
    public function testdefaultUserDataAvailable()
    {
        $logger = new Logger('error-log');
        $logger->pushHandler(new SentryMonologHandler());
        $handler = $logger->getHandlers()[0];
        
        // Setup the "fixture data" for this test
        $this->logInWithPermission('admin');
        
        $userDataThatWasSet = $handler->getClient()->getData()['user'];

        // Cannot get Member data at by default at initialisation time
        $this->assertEquals(1, $userDataThatWasSet['ID']);
        $this->assertEquals('ADMIN@example.org', $userDataThatWasSet['Email']);
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
        $handler = $logger->getHandlers()[0];
        $envThatWasSet = $handler->getClient()->getData()['env'];
        $xtraThatWasSet = $handler->getClient()->getData()['extra'];

        $this->assertEquals('live', $envThatWasSet);
        $this->assertArrayHasKey('foo', $xtraThatWasSet);
        $this->assertContains('bar', $xtraThatWasSet['foo']);
    }

}
