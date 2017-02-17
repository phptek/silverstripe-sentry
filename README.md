# Sentry.io integration for SilverStripe

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phptek/silverstripe-sentry/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phptek/silverstripe-sentry/?branch=master))

This is a simple module that binds Sentry.io to the error & exception handler of SilverStripe, thus allowing 
error and exception data to be sent to remote and local Sentry installations.

## Requirements

Besides having a Sentry instance to connect-to, SilverStripe v3.1.0+ should work fine. (Not tested with SilverStripe 4...yet)

## Setup

Add the Composer package as a dependency to your project:

	composer require silverstripe/sentry: 1.0

## Usage

### Basic

    SS_Log::add_writer(\SilverStripeSentry\SentryLogWriter::factory(), SS_Log::ERR, '<=');

### Specify an environment

    $config = [];
    $config['env'] = custom_env_func();
    SS_Log::add_writer(\SilverStripeSentry\SentryLogWriter::factory($config), SS_Log::ERR, '<=');

### Specify additional tags to filter on in Sentry UI

    $config = [];
    $config['env'] = custom_env_func();
    $config['tags'] = [
        'member-last-logged-in' => $member->getField('LastVisited')
    ],
    SS_Log::add_writer(\SilverStripeSentry\SentryLogWriter::factory($config), SS_Log::ERR, '<=');

### Specify additional arbitrary data to appear in Sentry UI

    $config = [];
    $config['env'] = custom_env_func();
    $config['tags'] = [
        'member-last-logged-in' => $member->getField('LastVisited')
    ],
    $config['extra'] = [
        'foo-route-exists' = in_array('foo', Controller::curr()->getRequest()->routeParams()) ? 'Yes' : 'No'
    ];
    SS_Log::add_writer(\SilverStripeSentry\SentryLogWriter::factory($config), SS_Log::ERR, '<=');

