<?php

/**
 * Class: SentryAdaptor.
 *
 * @author  Russell Michell 2017-2019 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Adaptor;

use PhpTek\Sentry\Exception\SentryLogWriterException;
use Sentry\State\Hub;
use Sentry\ClientBuilder;
use Sentry\State\Scope;
use Sentry\ClientInterface;
use Sentry\Severity;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Environment as Env;

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
     * Internal storage for context. Used only in the case of non-exception
     * data sent to Sentry.
     *
     * @var array
     */
    protected $context = [];

    /**
     * @return void
     */
    public function __construct()
    {
        $client = ClientBuilder::create($this->getOpts() ?: [])->getClient();
        Hub::getCurrent()->bindClient($client);

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
     * Configures Sentry "context" to display additional information about a SilverStripe
     * application's runtime and context.
     *
     * @param  string $field
     * @param  mixed  $data
     * @return void
     * @throws SentryLogWriterException
     */
    public function setContext(string $field, $data) : void
    {
        $options = Hub::getCurrent()->getClient()->getOptions();
        $options->setAttachStacktrace(true);

        switch ($field) {
            case 'env':
                $options->setEnvironment($data);
                $this->context['env'] = $data;
                break;
            case 'tags':
                Hub::getCurrent()->configureScope(function (Scope $scope) use($data) : void {
                    foreach ($data as $tagName => $tagData) {
                        $scope->setTag($tagName, $tagData);
                        $this->context['tags'][$tagName] = $tagData;
                    }
                });
                break;
            case 'user':
                Hub::getCurrent()->configureScope(function (Scope $scope) use($data) : void {
                    $scope->setUser($data, true);
                    $this->context['user'] = $data;
                });
                break;
            case 'extra':
                Hub::getCurrent()->configureScope(function (Scope $scope) use($data) : void {
                    foreach ($data as $extraKey => $extraData) {
                        $scope->setExtra($extraKey, $extraData);
                        $this->context['extra'][$extraKey] = $extraData;
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
     * Get _locally_ set contextual data, that we should be able to get from Sentry's
     * current {@link Scope}.
     *
     * Note: This (re) sets data to a new instance of {@link Scope} for passing to
     * captureMessage(). One would expect this to be set by default, as it is for
     * $record data sent to Sentry via captureException(), but it isn't.
     *
     * @return Scope
     */
    public function getContext() : Scope
    {
        $scope = new Scope();

        $scope->setUser($this->context['user']);

        foreach ($this->context['tags'] as $tagKey => $tagData) {
            $scope->setTag($tagKey, $tagData);
        }

        foreach ($this->context['extra'] as $extraKey => $extraData) {
            $scope->setExtra($extraKey, $extraData);
        }

        return $scope;
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
        $opts = [];

        // Extract env-vars from YML config or env
        if ($dsn = Env::getEnv('SENTRY_DSN')) {
            $opts['dsn'] = $dsn;
        }

        // Env vars take precedence over YML config in array_merge()
        $opts = Injector::inst()
            ->convertServiceProperty(array_merge($this->config()->get('opts') ?? [], $opts));

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
        } elseif (!$opt) {
            // Return all
            return $opts;
        }

        return null;
    }
}
