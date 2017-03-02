# Sentry.io integration for SilverStripe

[![Build Status](https://api.travis-ci.org/phptek/silverstripe-sentry.svg?branch=master)](https://travis-ci.org/phptek/silverstripe-sentry)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phptek/silverstripe-sentry/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phptek/silverstripe-sentry/?branch=master)
[![License](https://poser.pugx.org/phptek/sentry/license.svg)](https://github.com/phptek/silverstripe-sentry/blob/master/LICENSE.md)

[Sentry](https://sentry.io) is an error and exception aggregation service. It takes your application's errors and stores them for later analysis and debugging. 

Imagine this: You see exceptions before your client does. This means the error > report > debug > patch > deploy cycle is the most efficient it can possibly be.

This module binds Sentry.io and locally-hosted Sentry installations, to the error & exception handler of SilverStripe. If you've used systems like 
[RayGun](https://raygun.com), [Rollbar](https://rollbar.com), [AirBrake](https://airbrake.io/) and [BugSnag](https://www.bugsnag.com/) before, you'll know roughly what to expect.

## Requirements

 * PHP5.4+
 * SilverStripe v3.1.0+ < 4.0

## Setup

Add the Composer package as a dependency to your project:

	composer require phptek/sentry: dev-master

Configure your application or site with the Sentry DSN into your project's YML config:

    SilverStripeSentry\Adaptor\SentryClientAdaptor:
      opts:
        dsn: <sentry_dsn_configured_in_sentry_itself>

## Usage

Sentry is normally setup once in your _config.php file as follows:

### Basic

    SS_Log::add_writer(\SilverStripeSentry\SentryLogWriter::factory(), SS_Log::ERR, '<=');

### Specify an environment

If an environment is not specified, the default is to use the return value of `Director::get_environment_type()`.

    $config = ['env' => 'live'];
    SS_Log::add_writer(\SilverStripeSentry\SentryLogWriter::factory($config), SS_Log::ERR, '<=');

### Specify additional tags to filter-on in the Sentry UI

Sentry allows for custom key-value pairs to be sent as "tags". This then allows for
messages to be filtered via the Sentry UI.

    $config = [
        'env' => 'live',
        'tags' = [
            'Unique-ID' => $someObject->getFooID()
        ]
    ];
    SS_Log::add_writer(\SilverStripeSentry\SentryLogWriter::factory($config), SS_Log::ERR, '<=');

### Specify additional data to appear in the Sentry UI

Once a message is selected within the Sentry UI, additional, arbitrary data can be displayed 
to further help with debugging.

    $config = [
        'env' => 'live',
        'tags' => [
            'Unique-ID' => $someObject->getFooID()
        ],
        extra => [
            'Moon-Phase' => 'Full',
            'Tummy-Status' => 'Empty',
            'Cats' => 'Are furry'
        ]
    ];
    SS_Log::add_writer(\SilverStripeSentry\SentryLogWriter::factory($config), SS_Log::ERR, '<=');

### Additional data at runtime

Setting-up everything you want think you want to send, all from one spot is somewhat inflexible. Using the following however,
we can set additional, arbitrary and context-specific data to be sent to Sentry via calls to SS_Log::log and its optional
3rd parameter. You can then call SS_Log::log() in your project's customised Exception classes.

In order to inject additional data into a message at runtime, simply pass a 2-dimensional array
to the 3rd parameter of `SS_Log::log()` who's first key is "extra" and who's value is an array of values
comprising the data you wish to send:

    SS_Log::log('Help, my curry is too hot. I only asked for mild.', SS_Log::ERR, ['extra' => ['toilet' => 'now']]);

## TODO

A rough plan of features to implement. These will be contingent on those Sentry features
available to us in the default Raven_Client. However, there's nothing stopping us, stop-gapping what Raven_Client 
doesn't provide (e.g. "fingerprinting") with our own customised curl calls. These could be routed through
logic in Raven's Raven_CurlHandler class.

* [fingerprinting](https://docs.sentry.io/learn/rollups/#customize-grouping-with-fingerprints)
* [breadcrumbs](https://docs.sentry.io/learn/breadcrumbs/)
* Add feature-checking routine for features against the instance of Sentry being called
* Add release data ([see here](https://docs.sentry.io/clients/php/config/))
