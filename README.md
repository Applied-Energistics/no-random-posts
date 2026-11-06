# No Random Posts

WordPress plugin that disables core’s random content URLs (`/random/`, and the author, term, and post type variants) for every post type. There are no settings and nothing is stored.

WordPress 7.2 adds public URLs that 302 to a randomly chosen published post. A post type is included when it is publicly queryable and has an archive, unless it was registered with `randomable` set to false. Custom post types are often registered without that argument, so content that was never linked (invoices, for example) can be selected and exposed. The redirect URL can also be stored as its own URL by search engines and LLMs. Core sends `X-Robots-Tag: noindex` on the redirect response. That header does not stop the destination being discovered, and it is not a noindex control for the content itself.

WordPress resolves `/random/` with a random-order query (`ORDER BY RAND()` or the same class of query). That cost grows with the number of rows, and some hosts already restrict `RAND()`. Bots that request `/random/` over and over can amplify that into a load problem, including on large catalogues. Core has discussed an optimisation (https://core.trac.wordpress.org/ticket/64498#comment:58); it does not remove the scaling problem. This plugin makes those URLs 404 before that query runs.

A page, post, or term whose slug is actually `random` still loads. Only the random-redirect endpoints are removed. If nothing else owns that path, the request stays a 404.

The same description is in [readme.txt](readme.txt).

## Tests

The tests mock the WordPress hook API and do not need a database.

```bash
composer install
composer test
```
