<?php

/**
 * Class: SentryLogWriterException.
 *
 * @author  Russell Michell 2017-2021 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Exception;

/**
 * The module has its own exception subclasses to easily distinguish between project
 * and module exceptions.
 */
final class SentryLogWriterException extends \RuntimeException
{
}
