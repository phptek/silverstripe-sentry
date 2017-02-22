# Sentry.io integration for SilverStripe

[![License](https://poser.pugx.org/silverstripe/framework/license.svg)](https://github.com/phptek/silverstripe-sentry#license)
[![Scrutinizer](https://scrutinizer-ci.com/g/phptek/silverstripe-sentry/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phptek/silverstripe-sentry/?branch=master)

Sentry is an error and exception aggregation service. It takes your application's errors and stores them for later analysis and debugging. 

Imagine this: You see exceptions before your client does. This means the error > report > debug > patch > deploy cycle is the most efficient it can possibly be.

This module binds Sentry.io and locally-hosted Sentry installations, to the error & exception handler of SilverStripe. If you've used systems like 
[RayGun](https://raygun.com), [Rollbar](https://rollbar.com), [AirBrake](https://airbrake.io/) and [BugSnag](https://www.bugsnag.com/) before, you'll know roughly what to expect.

## Requirements

 * PHP5.4+
 * SilverStripe v3.1.0+ < 4.0

## Setup

Add the Composer package as a dependency to your project:

	composer require silverstripe/sentry: dev-master

Configure your application or site with the Sentry DSN into your project's YML config:

    SilverStripeSentry\Adaptors\SentryClientAdaptor:
      opts:
        dsn: <sentry_dsn_configured_in_sentry_itself>

## Usage

Sentry is normally setup once in your _config.php file as follows:

### Basic

    SS_Log::add_writer(\SilverStripeSentry\SentryLogWriter::factory(), SS_Log::ERR, '<=');

### Specify an environment

If an environment is not specified, the default is to use the return value of `Director::get_environment_type()`.

    $config = [];
    $config['env'] = custom_env_func();
    SS_Log::add_writer(\SilverStripeSentry\SentryLogWriter::factory($config), SS_Log::ERR, '<=');

### Specify additional tags to filter-on in the Sentry UI

Sentry allows for custom key-value pairs to be sent as "tags". This then allows for
messages to be filtered via the Sentry UI.

    $config = [];
    $config['env'] = custom_env_func();
    $config['tags'] = [
        'member-last-logged-in' => $member->getField('LastVisited')
    ],
    SS_Log::add_writer(\SilverStripeSentry\SentryLogWriter::factory($config), SS_Log::ERR, '<=');

### Specify additional data to appear in the Sentry UI

Once a message is selected within the Sentry UI, additional, arbitrary data can be displayed 
to further help with debugging.

    $config = [];
    $config['env'] = custom_env_func();
    $config['tags'] = [
        'member-last-logged-in' => $member->getField('LastVisited')
    ],
    $config['extra'] = [
        'foo-route-exists' = in_array('foo', Controller::curr()->getRequest()->routeParams()) ? 'Yes' : 'No'
    ];
    SS_Log::add_writer(\SilverStripeSentry\SentryLogWriter::factory($config), SS_Log::ERR, '<=');

### Add additional data at runtime through SS_Log:

In order to inject additional data into a message at runtime, simply pass
an array as the 3rd parameter to any calls made to `SS_Log::log()`.

NOTE: 23/02/17 This is not yet working.

    SS_Log::log('Help, my curry is too hot. I only asked for mild.', SS_Log::ERR, ['heat' => 'hot']);

