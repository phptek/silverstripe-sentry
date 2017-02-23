<?php

namespace SilverStripeSentry\Adaptor;

/**
 * Adaptor base class.
 * 
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package silverstripe/sentry
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
     * Set the data we need from the writer, to the {@link Raven_Client} itself
     *
     * @param string $field
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
     * @param  sarray $data
     * @param  string $trace
     * @return mixed
     */
    abstract public function send($message, $extras = [], $data, $trace);
    
}
