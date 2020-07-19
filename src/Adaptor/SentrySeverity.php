<?php

/**
 * Class: SentrySeverity.
 *
 * @author  Russell Michell 2019 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Adaptor;

use Sentry\Severity;

/**
 * SentrySeverity provides static methods that process or refine incoming severities
 * as integers (from PHP's severity constants, or as strings from userland config).
 */
class SentrySeverity
{
    /**
     * Maps PHP's internal error-types into those suited to {@link Severity}.
     *
     * @param  mixed int|string $severity The incoming level from userland code or
     *                                    PHP itself.
     * @return string
     */
    public static function process_severity($severity) : string
    {
        // Stringified PHP severities out of \debug_backtrace() like "notice"
        if (is_string($severity)) {
            $level = self::from_error($severity);
        // De-facto PHP severities as constants (ints) like E_NOTICE
        } elseif (is_numeric($severity)) {
            $level = Severity::fromError($severity);
        } else {
            // "Other"
            $level = Severity::ERROR;
        }

        return strtolower($level);
    }

    /**
     * Almost an exact replica of {@link Severity::fromError()}, except we're
     * dealing with string values passed to us from upstream processes.
     *
     * @param  string $severity An incoming severity.
     * @return string
     */
    public static function from_error(string $severity) : string
    {
        $severity = strtolower($severity);

        switch ($severity) {
            case 'deprecated':
            case 'user_deprecated':
            case 'warning':
            case 'user_warning':
                return Severity::WARNING;
            case 'error':
            case 'parse':
            case 'coreerror':
            case 'corwarning': // Possibly misspelling
            case 'corewarning':
            case 'compilerrror':
            case 'compilewarning':
                return Severity::FATAL;
            case 'recoverablerror':
            case 'user_error':
                return Severity::ERROR;
            case 'notice':
            case 'user_notice':
            case 'strict':
                return Severity::INFO;
            default:
                // It's an error until proven otherwise
                return Severity::ERROR;
        }
    }

}
