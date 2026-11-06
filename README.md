# No Random Posts

WordPress plugin that disables core’s random content URLs (`/random/`, and the author, term, and post type variants) for every post type. There are no settings and nothing is stored.

WordPress 7.2 adds public URLs that 302 to a randomly chosen published post. Those URLs can expose content that was never meant to be public or indexed. Core has no noindex control for the content. Custom post types such as invoices are often registered without careful public flags. A type that is publicly queryable and has an archive is included unless it was registered with `randomable` set to false. The redirect URL can also be stored as its own URL by search engines and LLMs. Core sends `X-Robots-Tag: noindex` on the redirect response. That header does not stop the destination being discovered.

The endpoints are on with no opt-in. They add new URLs to existing sites. That is awkward for enterprise sites, and for trust.

There is a further cost. WordPress resolves `/random/` with a random-order query (`ORDER BY RAND()` or the same class of query). The cost grows with the number of rows. Bots that request `/random/` over and over can turn that into a load problem. This plugin makes those URLs 404 before the query runs.

A page, post, or term whose slug is actually `random` still loads. Only the random-redirect endpoints are removed. If nothing else owns that path, the request stays a 404.

The same description is in [readme.txt](readme.txt).

## Tests

The tests mock the WordPress hook API and do not need a database.

```bash
composer install
composer test
```
