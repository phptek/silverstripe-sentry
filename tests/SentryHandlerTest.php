<?php

/**
 * Class: SentryHandlerTest.
 *
 * @author  Russell Michell 2017-2019 <russ@theruss.com>
 * @package phptek/sentry
 */

use SilverStripe\Dev\SapphireTest;
use Monolog\Logger;
use PhpTek\Sentry\Handler\SentryHandler;

/**
 * Exercises {@link SentryHandler}.
 */
class SentryHandlerTest extends SapphireTest
{
    /**
     * In the absence of fixture files, this is needed to force SaphhireTest into
     * creating us a test DB.
     *
     * @var boolean.
     */
    protected $usesDatabase = true;

    /**
     * Assert that the module's default tags make it through to the reporting process.
     *
     * @return void
     */
    public function testTagsAvailable() : void
    {        
        $logger = new Logger('error-log');
        $logger->pushHandler(SentryHandler::create());
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
     */
    public function testUserDataAvailable() : void
    {
        $logger = new Logger('error-log');
        $logger->pushHandler(SentryHandler::create());
        $handler = $logger->getHandlers()[0];
        
        // Setup the "fixture data" for this test
        $this->logInWithPermission('admin');
        
        $userDataThatWasSet = $handler->getClient()->getData()['user'];

        // Cannot get Member data at by default at initialisation time
        $this->assertEquals('ADMIN@example.org', $userDataThatWasSet['Email']);
    }
    
    /**
     * Assert that custom "additional" (extra) data, (extras, tags env etc) makes
     * it through to the reporting process.
     *
     * @return void
     */
    public function testExtrasAvailable() : void
    {
        // Register SentryLogWriter with some custom context
        $fixture = [
            'extra' => [
                'foo' => 'bar'
            ],
            'env' => 'live'
        ];
        $logger = new Logger('error-log');
        $logger->pushHandler(SentryHandler::create(100, true, $fixture));
        $handler = $logger->getHandlers()[0];
        $envThatWasSet = $handler->getClient()->getData()['env'];
        $xtraThatWasSet = $handler->getClient()->getData()['extra'];

        $this->assertEquals('live', $envThatWasSet);
        $this->assertArrayHasKey('foo', $xtraThatWasSet);
        $this->assertContains('bar', $xtraThatWasSet['foo']);
    }
}
