<?php

/**
 * Class: SentryClientAdaptor.
 *
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace SilverStripeSentry\Adaptor;

/**
 * The SentryClientAdaptor provides the base-class functionality for subclasses
 * to act as bridges between the PHP SDK and the SentryLogWriter class itself.
 * Any {@link SentryClientAdaptor} subclass should be able to be swapped-out and
 * used at any point.
 */

abstract class SentryClientAdaptor extends \Object
{

    /**
     * @param mixed $opt
     * @return mixed
     */
    protected function getOpts($opt)
    {
        $opts = $this->config()->opts;

        if (!is_null($opts) && !empty($opts[$opt])) {
            return $opts[$opt];
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

    /**
     * Physically transport the data to the configured Sentry host.
     *
     * @param  string $message
     * @param  array  $extras
     * @param  array  $data
     * @param  string $trace
     * @return mixed
     */
    abstract public function send($message, $extras = [], $data, $trace);
    
}
