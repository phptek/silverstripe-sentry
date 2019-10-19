# Sentry.io integration for SilverStripe

[![Build Status](https://api.travis-ci.org/phptek/silverstripe-sentry.svg?branch=master)](https://travis-ci.org/phptek/silverstripe-sentry)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phptek/silverstripe-sentry/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phptek/silverstripe-sentry/?branch=master)
[![License](https://poser.pugx.org/phptek/sentry/license.svg)](https://github.com/phptek/silverstripe-sentry/blob/master/LICENSE.md)

[Sentry](https://sentry.io) is an error and exception aggregation service. It takes your application's errors and stores them for later analysis and debugging. 

Imagine this: You see exceptions before your client does. This means the error > report > debug > patch > deploy cycle is the most efficient it can possibly be.

This module binds Sentry.io and locally-hosted Sentry installations, to the error & exception handler of SilverStripe. If you've used systems like 
[RayGun](https://raygun.com), [Rollbar](https://rollbar.com), [AirBrake](https://airbrake.io/) and [BugSnag](https://www.bugsnag.com/) before, you'll know roughly what to expect.

## Requirements

### SilverStripe 4

 * PHP >=7.0
 * SilverStripe ^4.0

### SilverStripe 3

 * PHP5.4+, <7.0
 * SilverStripe v3.1.0+, < 4.0

## Setup

Add the Composer package as a dependency to your project:

    composer require phptek/sentry

Note that v2.x and v3.x should work with the same setups, the latter simply uses a
newer version of the Sentry PHP SDK, and has a leaner codebase.

Note that with 3.x `SentryClientAdaptor` has been renamed to `SentryAdaptor`,
meaning your configuration will have to be updated accordingly.

Configure your application or site with the Sentry DSN into your project's YML config:

### SilverStripe 4

#### General Config ####

You can set your DSN as a first-class environment variable or via your project's `.env` file:

    SENTRY_DSN="http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44"

Or you can set it in YML config, where you gain a little more flexibility and control:

The following YML config will get you errors reported in all environment modes: `dev`, `test` and `live`: 

    ---
    Name: my-project-config-sentry
    After:
      - 'sentry-config'
    ---

    PhpTek\Sentry\Adaptor\SentryAdaptor:
      opts:
        # Example DSN only. Obviously you'll need to setup your own Sentry "Project"
        dsn: http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44

#### Conditional Config ####

The following YML config will get you errors reported just in `test` and `live` but not `dev`: 

    ---
    Name: my-project-config-sentry
    After:
      - 'sentry-config'
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

#### Proxies ####

Should your app require outgoing traffic to be passed through a proxy, the following config
will work.

    # Proxy constants
      http_proxy:
        host: '`MY_OUTBOUND_PROXY`'
        port: '`MY_OUTBOUND_PROXY_PORT`'

Note: As per the examles above, ensure your project's Sentry config is set to come *after* the module's own config, thus:

    After:
      - 'sentry-config'

#### Log Level ####

You can set the minimum log-level you're interested in, using the `log_level` config:

```
PhpTek\Sentry\Log\SentryLogger:
  # One of the permitted severities: DEBUG|INFO|WARNING|ERROR|FATAL
  log_level: WARNING
```

If you're interested to know how Sentry itself maps its own categories of message to
PHP's internals, see the `fromError()` method here: https://github.com/getsentry/sentry-php/blob/master/src/Severity.php

### SilverStripe 3

    phptek\Sentry\Adaptor\SentryClientAdaptor:
      opts:
        # Example DSN only. Obviously you'll need to setup your own Sentry "Project"
        dsn: http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44

## Usage

Sentry is normally setup once in your project's YML config or `_config.php` file. See the above examples and the [usage docs](docs/usage.md) for details and options.

## Known Issues

The stacktrace in SilverStripe 4 also sometimes includes the stacktrace of sentry itself! (See #26).

## TODO

See the [TODO docs](docs/todo.md) for more.

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
