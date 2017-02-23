<?php

namespace SilverStripeSentry\Exceptions;

/**
 * The module has its own exception subclass to easily distinquish between project
 * and module exceptions.
 * 
 * @author  Russell Michell 2017 <russ@theruss.com>
 * @package silverstripe/sentry
 */

final class SentryLogWriterException extends \RuntimeException
{
}
