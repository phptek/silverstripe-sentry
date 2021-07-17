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
     * Normalise a tag-key ready for display within Sentry's UI.
     *
     * @param  string $key
     * @return string
     */
    public static function normalise_tag_name(string $key): string
    {
        return strtolower(preg_replace("#[\-_\s]+#", '.', trim($key)));
    }

    /**
     * Normalise an "Additional Info" key ready for display within Sentry's UI.
     *
     * @param  string $key
     * @return string
     */
    public static function normalise_extras_name(string $key): string
    {
        return ucwords(strtolower(trim($key)));
    }
}
