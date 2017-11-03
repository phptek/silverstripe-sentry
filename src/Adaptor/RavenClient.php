<?php

/**
 * Class: RavenClient.
 *
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Adaptor;

use PhpTek\Sentry\Adaptor\SentryClientAdaptor,
    phpTek\Sentry\Exception\SentryLogWriterException,
    SilverStripe\Core\Config\Config;

/**
 * The RavenClient class simply acts as a bridge between the Raven PHP SDK and
 * the SentryLogWriter class itself. Any {@link SentryClientAdaptor} subclass
 * should be able to be swapped-out and used at any point.
 */

class RavenClient extends SentryClientAdaptor
{
    
    /**
     * It's an ERROR unless proven otherwise!
     * 
     * @var    string
     * @config
     */
    private static $default_error_level = 'ERROR';
    
    /**
     * @var Raven_Client
     */
    protected $client;
    
    /**
     * A mapping of log-level values between Zend_Log => Raven_Client
     * 
     * @var array
     */
    protected $logLevels = [
        'NOTICE'    => \Raven_Client::INFO,
        'WARN'      => \Raven_Client::WARNING,
        'ERR'       => \Raven_Client::ERROR,
        'EMERG'     => \Raven_Client::FATAL
    ];
    
    /**
     * @throws SentryLogWriterException
     * @return void
     */
    public function __construct()
    {        
        if (!$dsn = $this->getOpts('dsn')) {
            $msg = sprintf("Class: %s requires a DSN string to be set in config.", __CLASS__);
            throw new SentryLogWriterException($msg);
        }
        
        $this->client = new \Raven_Client($dsn);
        
        // Installs all available PHP error handlers when set
        if (Config::inst()->get(__CLASS__, 'install') === true) {
            $this->client->install();
        }
    }

    /**
     * Used in unit tests.
     *
     * @return Raven_Client
     */
    public function getSDK()
    {
        return $this->client;
    }

    /**
     * @inheritdoc
     */
    public function setData($field, $data)
    {
        switch($field) {
        case 'env':
            $this->client->setEnvironment($data);
            break;
        case 'tags':
            $this->client->tags_context($data);
            break;
        case 'user':
            $this->client->user_context($data);
            break;
        case 'extra':
            $this->client->extra_context($data);
            break;
        default:
            $msg = sprintf('Unknown field "%s" passed to %s().', $field, __FUNCTION__);
            throw new SentryLogWriterException($msg);
        }
    }
    
    /**
     * Simple accessor for data set to / on the client.
     * 
     * @return array
     */
    public function getData()
    {
        return [
            'env'   => $this->client->getEnvironment(),
            'tags'  => $this->client->context->tags,
            'user'  => $this->client->context->user,
            'extra' => $this->client->context->extra,
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function getLevel($level)
    {
        return isset($this->client->logLevels[$level]) ?
            $this->client->logLevels[$level] : 
            $this->client->logLevels[self::$default_error_level];
    }

}
