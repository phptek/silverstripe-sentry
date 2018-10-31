<?php

/**
 * Class: SentryClientAdaptor.
 *
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Adaptor;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use PhpTek\Sentry\Exception\SentryClientAdaptorException;

/**
 * The SentryClientAdaptor provides the base-class functionality for subclasses
 * to act as bridges between the PHP SDK and the SentryLogWriter class itself.
 * Any {@link SentryClientAdaptor} subclass should be able to be swapped-out and
 * used at any point.
 */

abstract class SentryClientAdaptor
{
    /**
     * Get various userland options to pass to Raven. Includes detecting and setting
     * proxy options too.
     *
     * @param  string $opt
     * @return mixed  null|string|array depending on whether $opts param is passed.
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
     * Set the data we need from the writer.
     *
     * @param string                 $field
     * @param mixed (string | array) $data
     */
    abstract public function setData($field, $data);

    /**
     * @return string
     */
    abstract public function getLevel($level);

}
