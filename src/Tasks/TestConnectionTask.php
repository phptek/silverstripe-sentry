<?php

namespace PhpTek\Sentry\Tasks;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;

class TestConnectionTask extends BuildTask
{
    /** @var string */
    protected $title = 'Test Sentry Configuration';

    /** @var string */
    protected $description = 'Captures message for all levels available';

    /**
     * Implement this method in the task subclass to
     * execute via the TaskRunner
     *
     * @param \SilverStripe\Control\HTTPRequest|null $request
     */
    public function run($request = null)
    {
        /** @var LoggerInterface $logger */
        $logger = Injector::inst()->get(LoggerInterface::class);

        foreach (Logger::getLevels() as $name => $value) {
            $func = strtolower($name);
            $logger->$func(sprintf("Testing Severity Level: %s", $name));
            self::output(sprintf("Security Level: %s", $name));
        }
    }

    private static function output(string $message)
    {
        $newLine = Director::is_cli() ? PHP_EOL : '<br/>';
        printf($message . $newLine);
    }
}
