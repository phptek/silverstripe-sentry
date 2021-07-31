# Release v[4.0.2](https://github.com/phptek/silverstripe-sentry/commits/4.0.2)

03fe8b4 (tag: 4.0.2, origin/4.0, 4.0) FIX: Losened PHP version constraint.

# Release v[4.0.1](https://github.com/phptek/silverstripe-sentry/commits/4.0.1)

519a4e8 (tag: 4.0.1) Merge pull request #73 from elliot-sawyer/bugfix/opts-array
28b510f ensure optsConfig returns an array if unset
a2ea129 MINOR: Typo in CHANGELOG link
9805438 MINOR: Fixed usage docs link in README. Updated CHANGELOG

# Release v[4.0.0](https://github.com/phptek/silverstripe-sentry/commits/4.0.0)

* 5e0d18b (HEAD -> 4.0, tag: 4.0.0, origin/4.0) NEW: Refs #29 from @halles Adds a connection test task.
* 7388608 Converted class methods into statics in SentryLogger.php
* 352ddfa Updated docs re: custom_stacktrace usage - get_opts() is only good for Sentry-specific options (removed stringy param)
* 57def09 Partially fixes #65 in that only a single message is sent to Sentry.
* 64e573d NEW: Release 4.0:
  - Fixes #6 (Documents Sentry's "release" feature)
  - Fixes #66 (Additional default tags)
  - Fixes #68 (Upgrade to Sentry 3.x)
  - Fixes #69 (INFO mis-reported as "ERROR")
  - Added more examples to `usage.md`
  - Added custom stacktrace feature & YML config
  - Bumped composer PHP versions to include all versions of 7.x and 8.x
  - Minor syntax formatting
  - Ensure all tags+extras appear as per Sentry's format: lower-case with '.' separator
  - Removed refs to Silverstripe 3 from README & moved config instructions into usage.md
  - Added metadata about the app in which the module is installed as a tag
  - Fixed incorrectly-named scope key
  - Adds guard around setting an empty context
  - Removed redundant Exception subclass
  - Simplified logic flow
* 06df067 MINOR: README Tweak

# Release v[3.0.10](https://github.com/phptek/silverstripe-sentry/commits/3.0.10)

* ecf6d2f (tag: 3.0.10, origin/3.0) MINOR: Tweaked README
* e08ce43 MUNOR: Tweaked README for additional SS3 config

# Release v[3.0.9](https://github.com/phptek/silverstripe-sentry/commits/3.0.9)

* f5280f4 (tag: 3.0.9) MINOR: Updated README to clarify package versions with respect to framework versions

# Release v[3.0.8](https://github.com/phptek/silverstripe-sentry/commits/3.0.8)

* a4eec40 (tag: 3.0.8) FIX: Removed redundant Monolog dep
* fb114c4 MINOR: Minor inline comments
* 3072e84 MINOR: Updated docs and comments

# Release v[3.0.7](https://github.com/phptek/silverstripe-sentry/commits/3.0.7)

* 9b8855f (tag: 3.0.7) Merge branch 'issue/64-missing-user-details'
* 5d22725 (origin/issue/64-missing-user-details) FIX: Fixes #64 by moving call to getCurrentUser() into write() context
* 50c0a67 FIX: Fixes deprecated set|get for Sentry's `Hub`

# Release v[3.0.6](https://github.com/phptek/silverstripe-sentry/commits/3.0.6)

* b4b423c (tag: 3.0.6) Merge pull request #59 from tractorcow/pull/fix-yml
* 9fec654 Fix issue with string quotes crashing updated symfony/yml
* e957c60 Merge pull request #54 from timezoneone/master
* b3e1b29 fix conditional config yml example
* 5b3c936 FIX: Ensure he 7.4 Travis build passes

# Release v[3.0.5](https://github.com/phptek/silverstripe-sentry/commits/3.0.5)

* 56b257a (tag: 3.0.5) FIX: Modded composer PHP versions - hat-tip to @shanholmes
* 09e8b41 Merge pull request #51 from tractorcow/fix/autoloading
* 3d9c2b3 Throwable import order
* 77c0fc9 Add autoloader directives Fix minor namespace bugs / style issues Fix indentation spaces Namespace tests Fixes #50

# Release v[3.0.4](https://github.com/phptek/silverstripe-sentry/commits/3.0.4)

* 21baa1c (tag: 3.0.4) Merge pull request #49 from halles/sentry-hub-patch
* aa02a89 Binds adaptor to existing Hub instead of creating an independent one Remove misleading comment Unnecessary default as the default minimum log level is set in the SentryHandler constructor Adds Configurable properties to SentryHandler. Set log_level in configuration in order to override value passed on construction. Sets default log_level value to WARNING in constructor as expected default for the module Updates README accordingly Remove as SentryAdaptor does not define what's the minimum report level

# Release v[3.0.3](https://github.com/phptek/silverstripe-sentry/commits/3.0.3)

* 08fd2ee (tag: 3.0.3) Merge pull request #45 from phptek/issue/42
* a898739 (origin/issue/42) FIX: Fixes #42 #26 Added stacktrace to non-exception messages - Added current scope/context to both types of message - Improved the backtrace() method (but is currently doing nothing - see comments) - Minor boyscouting

# Release v[3.0.2](https://github.com/phptek/silverstripe-sentry/commits/3.0.2)

* 14da436 Merge pull request #44 from halles/bugfix-replace-data-deprecated
* 7a1e5af Uses `true` when adding user data to the error reporting.
* 47ea652 MINOR: Updated README with YML override instructions.

# Release v[3.0.1](https://github.com/phptek/silverstripe-sentry/commits/3.0.1)

* b8c8949 MINOR: Docs.
* 7f89476 FIX: Fixes additional Travis config and bug in YML config opts.
* 0bd6420 FIX: Fixes ramining Travis problems.
* b1141f7 Removed support for PHP <7.2 (at least as far as Travis is concerned) otherwise tests fail due to Sentry SDK constraints on requirements for >=7.1
* 2524d5a FIX: Fixes #35 Allow options in both YML and from Env where the latter clobber the former. Use SENTRY_DSN in .env for example.
* cef15c2 FIX: Fixes #38 added Sentry context to "manual" logs.  - Worksaround the real issue, where context should be set in the factory (which it is, but isn't collected in the $record array).
* 66293b2 API: Upgrade to Sentry PHP SDK 2.2
* 2fadacf FIX: Refactored patch from #36 (fe9ac51c) into dedicated class  - Added calls to above into SentryAdapator::setData()  - Removed redundant getata() method tests (userland data not available from upstream Sentry logic)  - Added new SentrySeverity class for post-processing severity-levels  - Renamed existing test class and added some assertions for SentrySeverity  - Improved inline docs
* fe9ac51 FIX: Fixes #36 with greater range of severity detection.
* 01b2cf6 Missing class dependency
* ab11658 MINOR: Updated docs.
* 5034f2a MINOR: Removing unused cruft.
* 0c2cdfd Add class property and proc to read property after injection Allos override of minimum reporting level Add Task that tests Sentry Link Change config parameter name Specific comment for differences in versions 2 & 3 Sentry Testing Task Add all emergency levels available to options Change Namespace to correct one Default `log_level` set to `WARNING` Add output with new line wrapper. Removes unnecessary new lines.
* 25bd7e4 Removes EOL'ed php versions for testing Bump `silverstripe/recipe-cms` version requirements to next available as specified one doesn't support composer Wrong version Composer 2.x will error if names have casing Add mysql service Fix Test. ID will be unknown until user is mocked, but we can be certain that it will be an int. PHP version restricted to 7.0+ Changes recipe-cms version requirement to match php constrains
* 6c9d049 Fix bugs relating to `log_level` configuration setting
* 2b0a7e6 MINOR: Updated README
* b8caaee MINOR: Updated README
* f7355f7 FIX: Fixes #23 added minimum log-level via config.
* d615e58 FIX: Upgrade patches: - Incorrect type passed to Scope::setUser() - User-data array key typo - Bad type passed to SentryAdaptor::setData()
* 2e40c99 MINOR: Added type and return-type hinting.
* 3a5efb7 FIX: Tests passing.
* 9dcd8dd FIX: Further refactoring work: - Simplify logic wr/t "cleint" vs "logger" vs "adaptor" - Use more of the Sentry SDK
* efe50b7 FIX: Tests now run (But fail) - Minor tweaks to README and supported PHP versions
* c8acac2 NEW: Fixes #21 Upgrade to sentry/sdk 2.1 - Complete refactor, simplified class structure - Removes duplicate dependency on MonologHandler

