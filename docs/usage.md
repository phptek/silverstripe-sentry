# Usage

In your project's `_config.php`, set one of the following, depending on your use-case.

## Basic

### SilverStripe 3

    SS_Log::add_writer(\phptek\Sentry\SentryLogWriter::factory(), SS_Log::ERR, '<=');

### SilverStripe 4

Nothing to do, the logger is registered via YML config as a Monolog Handler.

## Set an environment

If an environment is not specified, the default is to use the return value of `Director::get_environment_type()`.

### SilverStripe 3

    $config = ['env' => 'live'];
    SS_Log::add_writer(\phptek\Sentry\SentryLogWriter::factory($config), SS_Log::ERR, '<=');

### SilverStripe 4

TBC

## Set tags

Sentry allows for custom key-value pairs to be recorded against each message that it is sent.
These are known as "tags", and they allow for fine-grained grouping and filtering of messages via the Sentry UI.

Note: It makes no sense to send hugely varying data in a tag. If it's unlikely that a tag you
wish to send, is ever going to be repeated, don't send it as a tag. Look at using the "Extras" feature (described below)
instead.

### SilverStripe 3

    $config = [
        'env' => 'live',
        'tags' = [
            'Unique-ID' => 44
        ]
    ];
    SS_Log::add_writer(\phptek\Sentry\SentryLogWriter::factory($config), SS_Log::ERR, '<=');

### SilverStripe 4

TBC

## Set extras

Once a message is selected within the Sentry UI, additional, arbitrary data can be displayed 
to further help with debugging. You can opt to send this as a consistent "baked-in" set of values
from within `_config.php` or at runtime, via passing the optional 3rd parameter to `SS_Log::log()`.

### SilverStripe 3

### Via _config.php

    $config = [
        'env' => 'live',
        'tags' => [
            'Unique-ID' => 44
        ],
        extra => [
            'Moon-Phase' => 'Full',
            'Tummy-Status' => 'Empty',
            'Cats' => 'Are furry'
        ]
    ];
    SS_Log::add_writer(\phptek\Sentry\SentryLogWriter::factory($config), SS_Log::ERR, '<=');

### Via SS_Log::log()

Setting-up everything you think you want to send, all from one spot in `_config.php` is somewhat inflexible. Using the following however,
we can set additional, arbitrary and context-specific data to be sent to Sentry via calls to `SS_Log::log()` and its optional
3rd parameter. You can then call `SS_Log::log()` in your project's customised Exception classes.

In order to inject additional data into a message at runtime, simply pass a 2-dimensional array
to the 3rd parameter of `SS_Log::log()` who's first key is "extra" and who's value is an array of values
comprising the data you wish to send:

    SS_Log::log('Help, my curry is too hot. I only asked for mild.', SS_Log::ERR, ['extra' => ['toilet' => 'now']]);

### SilverStripe 4

TBC