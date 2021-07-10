<?php

/**
 * Class: SentryHelper.
 *
 * @author  Russell Michell 2017-2021 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Helper;

class SentryHelper
{
    /**
     * Normalise a key ready for display within Sentry's UI.
     *
     * @param  string $key
     * @return string
     */
    public static function normalise_key(string $key): string
    {
        return strtolower(preg_replace("#[\-_\s]+#", '.', trim($key)));
    }
}
