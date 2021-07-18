# Sentry.io integration for SilverStripe

[![Build Status](https://api.travis-ci.org/phptek/silverstripe-sentry.svg?branch=master)](https://travis-ci.org/phptek/silverstripe-sentry)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phptek/silverstripe-sentry/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phptek/silverstripe-sentry/?branch=master)
[![License](https://poser.pugx.org/phptek/sentry/license.svg)](https://github.com/phptek/silverstripe-sentry/blob/master/LICENSE.md)

[Sentry](https://sentry.io) is an error and exception aggregation service. It takes your application's errors, aggregates them alongside configurable context and stores them for later analysis and debugging.

Imagine this: You see errors and exceptions before your clients do. The error > report > debug > patch > deploy cycle is therefore the most efficient it can possibly be.

This module binds Sentry.io and hosted Sentry installations, to the Monlog error logger in SilverStripe. If you've used systems like
[RayGun](https://raygun.com), [Rollbar](https://rollbar.com), [AirBrake](https://airbrake.io/) and [BugSnag](https://www.bugsnag.com/) before, you'll know roughly what to expect.

## Requirements

 * PHP >=7.0
 * SilverStripe ^4.0
 * `phptek/sentry` version 4.x (use 1.x for Silverstripe 3)

## Setup:

    composer require phptek/sentry

## Notes:

* Versions 2.x, 3.x and 4.x should work with the same Silverstripe v4 setups. v3+ simply use newer versions of the Sentry PHP SDK and have additional bugfixes and features.
* Version 3.x `SentryClientAdaptor` has been renamed to `SentryAdaptor` and `SentryLogWriter` was renamed to `SentryLogger`, so your existing configuration(s) may need to be updated accordingly.

## Config

You can set your DSN as a first-class environment variable or via your project's `.env` file:

    SENTRY_DSN="http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44"

Or you can set it in YML config, where you gain a little more flexibility and control:

The following will get you errors reported in all environment modes: `dev`, `test` and `live`:

    ---
    Name: my-project-config-sentry
    After:
      - 'sentry-config'
    ---

    PhpTek\Sentry\Adaptor\SentryAdaptor:
      opts:
        # Example DSN only. Obviously you'll need to setup your own Sentry "Project"
        dsn: http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44

### Conditional Config

The following will get you errors reported just in `test` and `live` but not `dev`:

    ---
    Name: my-project-config-sentry
    After:
      - 'sentry-config'
    ---

    ---
    Only:
      environment: test
    ---
    PhpTek\Sentry\Adaptor\SentryAdaptor:
      opts:
        # Example DSN only. Obviously you'll need to setup your own Sentry "Project"
        dsn: http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44
    ---
    Except:
      environment: test
    ---
    PhpTek\Sentry\Adaptor\SentryAdaptor:
      opts:
        # Example DSN only. Obviously you'll need to setup your own Sentry "Project"
        dsn: http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44
    ---
    Only:
      environment: dev
    ---
    PhpTek\Sentry\Adaptor\SentryAdaptor:
      opts:
        dsn: null
    ---

Please review the [usage docs](docs/en/usage.md) for further configuration and customisation options.

Notes:

* You can silence errors from `Injector` where "test" and "live" envs have `http_proxy` set, but "dev" environments don't. Just set `null` as the value. This applies to all YML config where some envs have a setting and others don't. For example:

```
...
    ---
    Only:
      environment: dev
    ---
    PhpTek\Sentry\Adaptor\SentryAdaptor:
      opts:
        dsn: null
        http_proxy: null
    ---
...
```

* As per the examples above, ensure your project's Sentry config is set to come *after* the module's own config, thus:

    After:
      - 'sentry-config'

### SilverStripe Framework v3

YML Config:

    phptek\Sentry\Adaptor\SentryClientAdaptor:
      opts:
        # Example DSN only. Obviously you'll need to setup your own Sentry "Project"
        dsn: http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44

mysite/_config.php:

    SS_Log::add_writer(\phptek\Sentry\SentryLogWriter::factory(), SS_Log::ERR, '<=');

## Usage

Sentry is normally setup once in your project's YML config or `_config.php` file. See the above examples and the [usage docs](docs/en/usage.md) for details and options.

## Support Me

If you like what you see, support me! I accept Bitcoin:

<table border="0">
	<tr>
		<td rowspan="2">
			<img src="https://bitcoin.org/img/icons/logo_ios.png" alt="Bitcoin" width="64" height="64" />
		</td>
	</tr>
	<tr>
		<td>
			<b>3KxmqFeVWoigjvXZoLGnoNzvEwnDq3dZ8Q</b>
		</td>
	</tr>
</table>

<p>&nbsp;</p>
<p>&nbsp;</p>
