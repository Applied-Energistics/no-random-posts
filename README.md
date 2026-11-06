# No Random Posts

WordPress 7.2 is about to add `/random/` URLs. A request 302s to a random public post. This is on for every site. There is no opt-in.

That can surface invoices, drafts-of-a-sort, and other types people never meant to be found. Custom post types are often registered without careful public flags. If a type is publicly queryable and has an archive, it is included unless someone set `randomable` to false. Core has no noindex for the content itself. Search engines and LLMs can keep the redirect.

No Random Posts turns those URLs off, for every post type, with no settings. A real page, post, or term whose slug is `random` still works. If nothing else owns the path, the request stays a 404.

There is a further cost. WordPress picks the post with `ORDER BY RAND()` (or the same class of query). The cost grows with the number of rows. Bots that request `/random/` over and over can turn that into a load problem. This plugin 404s the URL before that query runs.

Nothing is stored. On older WordPress the plugin does not fatal, and it does not change any other URL.

The same description is in [readme.txt](readme.txt).

## Tests

The tests mock the WordPress hook API. They do not need a database.

```bash
composer install
composer test
```
