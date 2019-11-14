<?php

namespace PhpTek\Sentry\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use Monolog\Logger;
use PhpTek\Sentry\Handler\SentryHandler;

class TestConnectionTask extends BuildTask
{
    protected $title = 'Test Sentry Configuration';

    protected $description = 'Triggers tesk task and sends an Error to Sentry';

    /**
     * Tests that connection is successful via a Task
     *
     * @param \SilverStripe\Control\HTTPRequest|null $request
     */
    public function run($request = null)
    {
        /** @var Logger $logger */
        $logger = Injector::inst()->createWithArgs(Logger::class, ['error-log'])
            ->pushHandler(SentryHandler::create());

        // Send an `ERROR` level message
        $logger->error('Testing Sentry Connection with level ERROR');
        self::output("Testing Sentry Connection with level ERROR");
    }

    /**
     * Prints out message
     * @param string $message
     */
    private static function output(string $message)
    {
        $newLine = Director::is_cli() ? PHP_EOL : '<br/>';
        printf($message . $newLine);
    }

}
