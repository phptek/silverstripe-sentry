<?php

/**
 * Class: SentryMonologHandler.
 *
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Handler;

use Monolog\Handler\RavenHandler,
    Monolog\Logger,
    SilverStripe\Dev\Backtrace,
    SilverStripe\Security\Member,
    PhpTek\Sentry\Log\SentryLogger;

/**
 * Monolog Handler for Sentry via Raven
 */

class SentryMonologHandler extends RavenHandler
{    
    /**
     * @var SentryClientAdaptor
     */
    protected $client;

    /**
     * @param  int   $level
     * @param  bool  $bubble
     * @param  array $extras Extra parameters that will become "tags" in Sentry.
     * @return void
     */
    public function __construct($level = Logger::DEBUG, $bubble = true, $extras = [])
    {        
        // Returns an instance of {@link SentryLogger}
        $logger = SentryLogger::factory($extras);
        $sdk = $logger->client->getSDK();
        $this->client = $logger->client;
        $this->client->setData('user', $this->getUserData(null, $logger));
        
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
        // The complete compliment of these data come via the Raven_Client::xxx_context() methods
        $record = [
            'level'      => $record['level'],
            'formatted'  => $record['formatted'],
            'channel'    => $record['channel'],
            'timestamp'  => $record['datetime']->getTimestamp(),
            'extra'      => !empty($record['extra']) ? $record['extra'] : [],
            'stacktrace' => $this->backtrace($record),
            'stack'      => true,
        ];
        
        // Will use one of RavenHandler::captureException() or RavenHandler::captureMessage()
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
        $bt = debug_backtrace();
            
        // Push current line into context
        array_unshift($bt, [
            'file' => !empty($bt['file']) ? $bt['file'] : 'N/A',
            'line' => !empty($bt['line']) ? $bt['line'] : 'N/A',
            'function' => '',
            'class' => '',
            'type' => '',
            'args' => [],
        ]);
        
        $bt = Backtrace::filter_backtrace($bt, [
            'PhpTek\Sentry\Handler\SentryMonologHandler->write',
            'PhpTek\Sentry\Handler\SentryMonologHandler->backtrace',
        ]);
        
        return $bt;
    }
    
    /**
     * Returns a default set of additional data specific to the user's part in
     * the request.
     * 
     * @param  Member $member
     * @return array
     */
    private function getUserData(Member $member = null, $logger)
    {
        if (!$member) {
            $member = Member::currentUser();
        }
        
        return [
            'IPddress'  => $logger->getIP(),
            'ID'        => $member ? $member->getField('ID') : SentryLogger::SLW_NOOP,
            'Email'     => $member ? $member->getField('Email') : SentryLogger::SLW_NOOP,
        ];
    }
    
}
