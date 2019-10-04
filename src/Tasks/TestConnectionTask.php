<?php

namespace PhpTek\Sentry\Tasks;

use Sentry\Severity;
use SilverStripe\Dev\BuildTask;
use function Sentry\captureMessage;

class TestConnectionTask extends BuildTask
{
    protected $title = 'Test Sentry Configuration';

    protected $description = 'Captures message for all levels available';

    /**
     * Implement this method in the task subclass to
     * execute via the TaskRunner
     *
     * @param \SilverStripe\Control\HTTPRequest|null $request
     */
    public function run($request = null)
    {
        /**
         * Tests that connection is successful
         */
        $response = captureMessage('Testing Sentry Connection with level WARNING', new Severity(Severity::WARNING));
        printf("Testing Sentry Connection with level WARNING: %s <br/>\n", ($response ? 'OK': 'FAILED'));
    }
}
