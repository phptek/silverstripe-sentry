<?php

/**
 * Class: SentryLogWriterTest.
 *
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package phptek/sentry
 */

use SilverStripeSentry\SentryLogWriter;

/**
 * Excercises RavenClient.
 */
class RavenClientTest extends \SapphireTest
{
    /**
     *
     */
	public function setUp()
    {
        parent::setUp();

        // No idea why these are needed, but although the suite runs, we always see
        // nest/unnest() errors from phpunit..
        Injector::nest();
        Config::nest();

		\Phockito::include_hamcrest(true);
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

        $ravenSDKClient = $writer->getClient()->getSDK();
        $tagsThatWereSet = $ravenSDKClient->context->tags;

        $this->assertArrayHasKey('Request-Method', $tagsThatWereSet);
        $this->assertArrayHasKey('Request-Type', $tagsThatWereSet);
        $this->assertArrayHasKey('SAPI', $tagsThatWereSet);
        $this->assertArrayHasKey('SS-Version', $tagsThatWereSet);

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
        \SS_Log::log('You have 20 seconds to reach minimum safe distance.', \SS_Log::ERR);

        $ravenSDKClient = $writer->getClient()->getSDK();
        $envThatWasSet = $ravenSDKClient->getEnvironment();
        $xtraThatWasSet = $ravenSDKClient->context->extra;

        $this->assertEquals('live', $envThatWasSet);
        $this->assertArrayHasKey('foo', $xtraThatWasSet);
        $this->assertContains('bar', $xtraThatWasSet['foo']);

        // Cleanup
        \SS_Log::remove_writer($writer);
    }

}
