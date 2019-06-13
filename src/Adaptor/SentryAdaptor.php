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
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Security;
use PhpTek\Sentry\Log\SentryLogger;

/**
 * The SentryAdaptor provides functionality bridge between the PHP SDK and
 * {@link SentryLogger} itself.
 */
class SentryAdaptor
{
    /**
     * It's an ERROR unless proven otherwise!
     *
     * @var    string
     * @config
     */
    private static $default_error_level = 'ERR';

    /**
     * @var ???
     */
    protected $client;

    /**
     * @return void
     */
    public function __construct()
    {
        $client = ClientBuilder::create($this->getOpts() ?: [])->getClient();
        Hub::setCurrent(new Hub($client));

        $this->client = $client;
    }

    /**
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
        return isset($this->logLevels[$level]) ?
            $this->logLevels[$level] :
            $this->logLevels[self::$default_error_level];
    }

    /**
     * Get various userland options to pass to Raven. Includes detecting and setting
     * proxy options too.
     *
     * @param  string $opt
     * @return mixed  string|array depending on whether $opts param is passed.
     */
    protected function getOpts($opt = '')
    {
        // Extract env-vars from YML config
        $opts = Injector::inst()->convertServiceProperty(Config::inst()->get(__CLASS__, 'opts'));

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

    /**
     * Returns a default set of additional data specific to the user's part in
     * the request.
     *
     * @param  Member       $member
     * @param  SentryLogger $logger
     * @return array
     */
    public function getUserData(Member $member = null, SentryLogger $logger) : array
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        return [
            'IPddress' => $logger->getIP(),
            'ID'       => $member ? $member->getField('ID') : SentryLogger::SLW_NOOP,
            'Email'    => $member ? $member->getField('Email') : SentryLogger::SLW_NOOP,
        ];
    }
}
