# TODO

A rough plan of features to implement. These will be contingent on those Sentry features
available to us in the default Raven_Client. However, there's nothing stopping us, stop-gapping what Raven_Client 
doesn't provide (e.g. "fingerprinting") with our own customised curl calls. These could be routed through
logic in Raven's Raven_CurlHandler class.

* [fingerprinting](https://docs.sentry.io/learn/rollups/#customize-grouping-with-fingerprints)
* [breadcrumbs](https://docs.sentry.io/learn/breadcrumbs/)
* Add feature-checking routine for features against the instance of Sentry being called
* Add release data ([see here](https://docs.sentry.io/clients/php/config/))
* SilverStripe 4 support via the Monolog RavenHandler
