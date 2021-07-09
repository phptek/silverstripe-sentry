<?php

namespace PhpTek\Sentry\Tests;

/**
 * Class: SentrySeverityTest.
 *
 * @author  Russell Michell 2017-2021 <russ@theruss.com>
 * @package phptek/sentry
 */

use SilverStripe\Dev\SapphireTest;
use PhpTek\Sentry\Adaptor\SentrySeverity;

/**
 * Exercises {@link SentrySeverity}.
 */
class SentrySeverityTest extends SapphireTest
{
    /**
     * Ensure all permutations of errors have an equivalent in our Sentry handler
     */
    public function testProcessSeverity()
    {
        // Some userland config
        $this->assertEquals('warning', SentrySeverity::process_severity('WARNING'));
        $this->assertEquals('fatal', SentrySeverity::process_severity('ERROR'));
        $this->assertEquals('error', SentrySeverity::process_severity('WIBBLE'));
        $this->assertEquals('info', SentrySeverity::process_severity('INFO'));

        // Some stringified PHP severities
        $this->assertEquals('warning', SentrySeverity::process_severity('warning'));
        $this->assertEquals('fatal', SentrySeverity::process_severity('error'));
        $this->assertEquals('info', SentrySeverity::process_severity('notice'));
        $this->assertEquals('info', SentrySeverity::process_severity('info'));

        // De-facto PHP severities
        $this->assertEquals('warning', SentrySeverity::process_severity(E_WARNING));
        $this->assertEquals('fatal', SentrySeverity::process_severity(E_ERROR));
        $this->assertEquals('info', SentrySeverity::process_severity(E_NOTICE));
    }
}
