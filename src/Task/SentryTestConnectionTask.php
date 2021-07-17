<?php

/**
 * Class: SentryTestConnectionTask.
 *
 * @author  Matías Halles 2019-2021 <matias.halles@gmail.com>
 * @author  Russell Michell 2021 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Tasks;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\HTTPRequest;
use Silverstripe\Control\Director;
use PhpTek\Sentry\Handler\SentryHandler;

/**
 * Tests a connection to Sentry, without having to hack directly inside
 * the parent app or site.
 *
 * See more examples in docs/en/usage.md.
 */
class SentryTestConnectionTask extends BuildTask
{
    /**
     * @var string
     */
    protected $title = 'Test Sentry Configuration';

    /**
     * @var string
     */
    protected $description = 'Captures message for all levels available';

    /**
     * Implement this method in the task subclass to
     * execute via the TaskRunner
     *
     * @param HTTPRequest $request
     * @return void
     */
    public function run($request = null): void
    {
        /** @var LoggerInterface $logger */
        $logger = Injector::inst()->createWithArgs(Logger::class, ['error-log'])
            ->pushHandler(SentryHandler::create());

        foreach (array_keys(Logger::getLevels()) as $name) {
            $func = strtolower($name);
            $logger->$func(sprintf("Testing Severity Level: %s", $name));

            self::output(sprintf("Tested Security Level: %s", $name));
        }

        self::output("\nDone!");
    }

    /**
     * Simple output logging.
     *
     * @param string $message
     * @return void
     */
    private static function output(string $message): void
    {
        $newLine = Director::is_cli() ? PHP_EOL : '<br/>';
        printf($message . $newLine);
    }

}
