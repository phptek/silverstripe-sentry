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
use Sentry\ClientInterface;
use Sentry\Severity;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use PhpTek\Sentry\Adaptor\SentrySeverity;

/**
 * The SentryAdaptor provides a functionality bridge between the getsentry/sentry
 * PHP SDK and {@link SentryLogger} itself.
 */
class SentryAdaptor
{
    use Configurable;

    /**
     * @var ClientInterface
     */
    protected $sentry;

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
    public function getSDK() : ClientInterface
    {
        return $this->sentry;
    }

    /**
     * Configures Sentry to display additional information about a SilverStripe
     * application's runtime and context.
     * 
     * @param  string $field
     * @param  mixed  $data
     * @return void
     * @throws SentryLogWriterException
     */
    public function setData(string $field, $data) : void
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
                    $scope->setUser($data);
                });
                break;
            case 'extra':
                Hub::getCurrent()->configureScope(function (Scope $scope) use($data) : void {
                    foreach ($data as $extraKey => $extraData) {
                        $scope->setExtra($extraKey, $extraData);
                    }
                });
                break;
            case 'level':
                Hub::getCurrent()->configureScope(function (Scope $scope) use($data) : void {
                    $scope->setLevel(new Severity(SentrySeverity::process_severity($level = $data)));
                });
                break;
            default:
                $msg = sprintf('Unknown field "%s" passed to %s().', $field, __FUNCTION__);
                throw new SentryLogWriterException($msg);
        }
    }

    /**
     * Get various userland options to pass to Raven. Includes detecting and setting
     * proxy options too.
     *
     * @param  string $opt
     * @return mixed  string|array|null depending on whether $opts is passed.
     */
    protected function getOpts(string $opt = '')
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
