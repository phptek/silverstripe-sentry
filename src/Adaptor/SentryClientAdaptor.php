<?php

/**
 * Class: SentryClientAdaptor.
 *
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Adaptor;

use SilverStripe\Core\Config\Config;

/**
 * The SentryClientAdaptor provides the base-class functionality for subclasses
 * to act as bridges between the PHP SDK and the SentryLogWriter class itself.
 * Any {@link SentryClientAdaptor} subclass should be able to be swapped-out and
 * used at any point.
 */

abstract class SentryClientAdaptor
{

    /**
     * @param  mixed $opt
     * @return mixed
     */ 
    protected function getOpts($opt)
    {
        $opts = Config::inst()->get(__CLASS__, 'opts');

        if (!empty($opts[$opt])) {
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
    
}
