<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *
 */

namespace PhpTek\Sentry\Monolog\Handler;

use Monolog\Handler\RavenHandler;

/**
 * Subclasses RavenHandler purely to overload its `write()` method.
 */
class SentryRavenHandler extends RavenHandler
{
    /**
     * Overloads RavenHandler::write() to allow a stacktrace to be passed
     * into Sentry. Otherwise, this method is identical to the one included
     * in the Monolog package.
     *
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $previousUserContext = false;
        $options = [];
        $options['level'] = $this->logLevels[$record['level']];
        $options['tags'] = [];

        if (!empty($record['extra']['tags'])) {
            $options['tags'] = array_merge($options['tags'], $record['extra']['tags']);
            unset($record['extra']['tags']);
        }

        if (!empty($record['context']['tags'])) {
            $options['tags'] = array_merge($options['tags'], $record['context']['tags']);
            unset($record['context']['tags']);
        }

        if (!empty($record['context']['fingerprint'])) {
            $options['fingerprint'] = $record['context']['fingerprint'];
            unset($record['context']['fingerprint']);
        }

        if (!empty($record['context']['logger'])) {
            $options['logger'] = $record['context']['logger'];
            unset($record['context']['logger']);
        } else {
            $options['logger'] = $record['channel'];
        }

        foreach ($this->getExtraParameters() as $key) {
            foreach (['extra', 'context'] as $source) {
                if (!empty($record[$source][$key])) {
                    $options[$key] = $record[$source][$key];

                    unset($record[$source][$key]);
                }
            }
        }

        if (!empty($record['context'])) {
            $options['extra']['context'] = $record['context'];

            if (!empty($record['context']['user'])) {
                $previousUserContext = $this->ravenClient->context->user;
                $this->ravenClient->user_context($record['context']['user']);
                unset($options['extra']['context']['user']);
            }
        }

        if (!empty($record['extra'])) {
            $options['extra']['extra'] = $record['extra'];
        }

        // New for phptek/sentry
        $stack = false;
        if (!empty($record['stack'])) {
            $stack = (bool) $record['stack'];
        }

        if (!empty($this->release) && !isset($options['release'])) {
            $options['release'] = $this->release;
        }

        if (isset($record['context']['exception']) && ($record['context']['exception'] instanceof \Exception || (PHP_VERSION_ID >= 70000 && $record['context']['exception'] instanceof \Throwable))) {
            $options['extra']['message'] = $record['formatted'];
            $this->ravenClient->captureException($record['context']['exception'], $options);
        } else {
            $this->ravenClient->captureMessage($record['formatted'], [], $options, $stack);
        }

        if ($previousUserContext !== false) {
            $this->ravenClient->user_context($previousUserContext);
        }
    }
}
