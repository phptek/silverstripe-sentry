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

