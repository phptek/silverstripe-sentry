<?php

/**
 * Class: SentryAdaptor.
 *
 * @author  Russell Michell 2017-2019 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Adaptor;

use Sentry\State\Hub;
use Sentry\ClientBuilder;
use Sentry\State\Scope;
use Sentry\Severity;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;

/**
 * The SentryAdaptor provides functionality bridge between the PHP SDK and
 * {@link SentryLogger} itself.
 */
class SentryAdaptor
{
    use Configurable;
    
    /**
     * It's an ERROR unless proven otherwise!
     *
     * @var    string
     * @config
     */
    private static $default_error_level = 'ERR';

    /**
     * @var ClientInterface
     */
    protected $sentry;
        
    /**
     * A mapping of log-level values between Zend_Log => Raven_Client
     *
     * @var array
     */
    protected $logLevels = [
        'NOTICE' => Severity::INFO,
        'WARN'   => Severity::WARNING,
        'ERR'    => Severity::ERROR,
        'EMERG'  => Severity::FATAL
    ];

    /**
     * @return void
     */
    public function __construct()
    {
        $client = ClientBuilder::create($this->getOpts() ?: [])->getClient();
        Hub::setCurrent(new Hub($client));

        $this->sentry = $client;
    }

    /**
     * @return ClientInterface
     */
    public function getSDK()
    {
        return $this->sentry;
    }

    /**
     * @inheritdoc
     */
    public function setData(string $field, $data)
    {
        $options = Hub::getCurrent()->getClient()->getOptions();

        switch ($field) {
            case 'env':
                $options->setEnvironment($data);
                break;
            case 'tags':
                Hub::getCurrent()->configureScope(function (Scope $scope) use($data) : void {
                    foreach ($data as $tagName => $tagData) {
                        $scope->setTag($tagName, $tagData);
                    }
                });
                break;
            case 'user':
                Hub::getCurrent()->configureScope(function (Scope $scope) use($data) : void {
                    foreach ($data as $userKey => $userData) {
                        $scope->setUser($data);
                    }
                });
                break;
            case 'extra':
                Hub::getCurrent()->configureScope(function (Scope $scope) use($data) : void {
                    foreach ($data as $extraKey => $extraData) {
                        $scope->setExtra($extraKey, $extraData);
                    }
                });
                break;
            default:
                $msg = sprintf('Unknown field "%s" passed to %s().', $field, __FUNCTION__);
                throw new SentryLogWriterException($msg);
        }
    }

    /**
     * Simple getter for data set to / on the sentry client.
     *
     * @return array
     */
    public function getData() : array
    {
        $options = Hub::getCurrent()->getClient()->getOptions();
        $data = [];
        
        Hub::getCurrent()->configureScope(function (Scope $scope) use (&$data) : void {
                $data['user'] = $scope->getUser();
                $data['tags'] = $scope->getTags();
                $data['extra'] = $scope->getExtra();
        });
        
        return [
            'env'   => $options->getEnvironment(),
            'tags'  => $data['tags'] ?? [],
            'user'  => $data['user'] ?? [],
            'extra' => $data['extra'] ?? [],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getLevel($level)
    {
        return isset($this->logLevels[$level]) ?
            $this->logLevels[$level] :
            $this->logLevels[self::$default_error_level];
    }

    /**
     * Get various userland options to pass to Raven. Includes detecting and setting
     * proxy options too.
     *
     * @param  string $opt
     * @return mixed  string|array|null depending on whether $opts param is passed.
     */
    protected function getOpts($opt = '')
    {
        // Extract env-vars from YML config
        $opts = Injector::inst()->convertServiceProperty($this->config()->get('opts'));

        // Deal with proxy settings. Raven_Client permits host:port format but SilverStripe's
        // YML config only permits single backtick-enclosed env/consts per config
        if (!empty($opts['http_proxy'])) {
            if (!empty($opts['http_proxy']['host']) && !empty($opts['http_proxy']['port'])) {
                $opts['http_proxy'] = sprintf(
                    '%s:%s',
                    $opts['http_proxy']['host'],
                    $opts['http_proxy']['port']
                );
            }
        }

        if ($opt && !empty($opts[$opt])) {
            // Return one
            return $opts[$opt];
        } else if (!$opt) {
            // Return all
            return $opts;
        }

        return null;
    }
}
