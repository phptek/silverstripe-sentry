# Sentry.io integration for SilverStripe

[![CI](https://github.com/phptek/silverstripe-sentry/actions/workflows/ci.yml/badge.svg)](https://github.com/phptek/silverstripe-sentry/actions/workflows/ci.yml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phptek/silverstripe-sentry/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phptek/silverstripe-sentry/?branch=master)
[![License](https://poser.pugx.org/phptek/sentry/license.svg)](https://github.com/phptek/silverstripe-sentry/blob/master/LICENSE.md)

[Sentry](https://sentry.io) and [Glitchtip](https://glitchtip.com) are error and exception aggregation services. Systems like these take your application's errors, aggregate them alongside configurable context and store them for triage and analysis.

Imagine this: You see errors and exceptions before your clients do. The error > report > debug > patch > deploy cycle is therefore the most efficient it can possibly be.

This module binds sentry.io, app.glitchtip.com and on-prem hosted Sentry/Glitchtip installations to the Monlog error logger in Silverstripe. If you've used systems like [RayGun](https://raygun.com), [Rollbar](https://rollbar.com), [AirBrake](https://airbrake.io/) and [BugSnag](https://www.bugsnag.com/) before, you'll know roughly what to expect.

## Requirements

 See `composer.json`

## Setup:

    composer require phptek/sentry

## Notes:

* Version 5.x is aimed at Silverstripe 5.
* Versions 2.x, 3.x and 4.x should work with the same Silverstripe v4 setups. v3+ simply use newer versions of the Sentry PHP SDK and have additional bugfixes and features.
* Version 3.x `SentryClientAdaptor` has been renamed to `SentryAdaptor` and `SentryLogWriter` was renamed to `SentryLogger`, so your existing configuration(s) may need to be updated accordingly.

## Config

You can set your DSN as a first-class environment variable in `.env` or your CI config:

    SENTRY_DSN="http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44"

You can also set it in your project's YML config:

    ---
    Name: my-project-config-sentry
    After:
      - 'sentry-config'
    ---

    # Send errors reported for all environment modes: `dev`, `test` and `live` envs

    PhpTek\Sentry\Adaptor\SentryAdaptor:
      opts:
        # Example DSN only. Obviously you'll need to setup your own Sentry "Project"
        dsn: http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44

### Conditional Config

    ---
    Name: my-project-config-sentry
    After:
      - 'sentry-config'
    ---

    # Send errors reported just in `test` and `live`, but not `dev` envs

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

* Silence `Injector` errors where `test` and `live` envs have `http_proxy` set but `dev` environments don't: Provide a value of `null`, which applies to all YML config where one env has a setting and another doesn't. For example:

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

* As per the examples above, ensure your project's config is set to come *after* the module's own config, thus:

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

Sentry is normally setup once in your project's `.env`, YML config or even `_config.php` file. See the above examples and the [usage docs](docs/en/usage.md) for details and options.

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
