<?php

/**
 * Class: RavenClientTest.
 *
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package phptek/sentry
 */

use phptek\Sentry\SentryLogWriter;

/**
 * Excercises RavenClient.
 */
class RavenClientTest extends \SapphireTest
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
    }

    /**
     * Assert that the module's default tags make it through to the reporting process.
     *
     * @return void
     */
    public function testDefaultTagsAvailable()
    {
        $writer = SentryLogWriter::factory();
        \SS_Log::add_writer($writer, \SS_Log::ERR, '<=');

        // Invoke SentryLogWriter::_write()
        \SS_Log::log('You have 30 seconds to reach minimum safe distance.', \SS_Log::ERR);

        $tagsThatWereSet = $writer->getClient()->getData()['tags'];

        $this->assertArrayHasKey('Request-Method', $tagsThatWereSet);
        $this->assertArrayHasKey('Request-Type', $tagsThatWereSet);
        $this->assertArrayHasKey('SAPI', $tagsThatWereSet);
        $this->assertArrayHasKey('SS-Version', $tagsThatWereSet);

        // Cleanup
        \SS_Log::remove_writer($writer);
    }

    /**
     * Assert that custom "additional" (user) data makes it through to the
     * reporting process.
     *
     * @return void
     */
    public function testdefaultUserDataAvailable()
    {
        $writer = SentryLogWriter::factory();
        \SS_Log::add_writer($writer, \SS_Log::ERR, '<=');
        
        // Setup the "fixture data" for this test
        $this->logInWithPermission('admin');

        $userDataThatWasSet = $writer->getClient()->getData()['user'];

        // Cannot get Member data at by default at initialisation time
        $this->assertEquals('Unavailable', $userDataThatWasSet['ID']);
        $this->assertEquals('Unavailable', $userDataThatWasSet['Email']);

        // Cleanup
        \SS_Log::remove_writer($writer);
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
        $writer = SentryLogWriter::factory($fixture);
        \SS_Log::add_writer($writer, \SS_Log::ERR, '<=');

        // Invoke SentryLogWriter::_write()
        \SS_Log::log('You have 10 seconds to comply.', \SS_Log::ERR);

        $envThatWasSet = $writer->getClient()->getData()['env'];
        $xtraThatWasSet = $writer->getClient()->getData()['extra'];

        $this->assertEquals('live', $envThatWasSet);
        $this->assertArrayHasKey('foo', $xtraThatWasSet);
        $this->assertContains('bar', $xtraThatWasSet['foo']);

        // Cleanup
        \SS_Log::remove_writer($writer);
    }

}
