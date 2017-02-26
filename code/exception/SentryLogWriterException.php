<?php

/**
 * Class: SentryLogWriterException.
 *
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package silverstripe/sentry
 */

namespace SilverStripeSentry\Exception;

/**
 * The module has its own exception subclasses to easily distinquish between project
 * and module exceptions.
 */

final class SentryLogWriterException extends \RuntimeException
{
}
