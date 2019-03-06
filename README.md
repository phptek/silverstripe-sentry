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

 * PHP5.6+, <7.2
 * SilverStripe v4.0.0+

### SilverStripe 3

 * PHP5.4+, <7.0
 * SilverStripe v3.1.0+, < 4.0

## Setup

Add the Composer package as a dependency to your project:

### SilverStripe 3

    composer require phptek/sentry: 1.x

### SilverStripe 4

    composer require phptek/sentry: 2.x

Configure your application or site with the Sentry DSN into your project's YML config:

### SilverStripe 4

#### General Config ####

The following YML config will get you errors reported in all environment modes: `dev`, `test` and `live`: 

    PhpTek\Sentry\Adaptor\SentryClientAdaptor:
      opts:
        # Example DSN only. Obviously you'll need to setup your own Sentry "Project"
        dsn: http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44

#### Conditional Config ####

The following YML config will get you errors reported just in `test` and `live` but not `dev`: 

    ---
    Only:
      environment: test
    ---
    PhpTek\Sentry\Adaptor\SentryClientAdaptor:
      opts:
        # Example DSN only. Obviously you'll need to setup your own Sentry "Project"
        dsn: http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44
    ---
    Except:
      environment: test
    ---
    PhpTek\Sentry\Adaptor\SentryClientAdaptor:
      opts:
        # Example DSN only. Obviously you'll need to setup your own Sentry "Project"
        dsn: http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44
    ---
    Only:
      environment: dev
    ---
    PhpTek\Sentry\Adaptor\SentryClientAdaptor:
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

Note: For ~2.0.0 you'll need to ensure your project's config that includes the Sentry DSN above, is set to 
be after the module's config, thus:

    After: 'sentryconfig'

This is because a baked-in dummy DSN needed to be added to the module's config for unit-testing. This will
need to remain in-place until the tests can be fixed to use the `Config` system properly.

### SilverStripe 3

    phptek\Sentry\Adaptor\SentryClientAdaptor:
      opts:
        # Example DSN only. Obviously you'll need to setup your own Sentry "Project"
        dsn: http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44

## Usage

Sentry is normally setup once in your project's YML config or `_config.php` file. See the [usage docs](docs/usage.md) for details and options.

## Known Issues

The stacktrace does not show in SilverStripe 4. We're using the `Monolog` package's `RavenHandler` which isn't as fully functional.
There is a PR in that fixes the problem here: https://github.com/Seldaek/monolog/pull/1075.

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
			<b>bc1qmg0jjtmu3fmm53mkvw69xz8kerq3l3lnh6529d</b>
		</td>
	</tr>
</table>

<p>&nbsp;</p>
<p>&nbsp;</p>
