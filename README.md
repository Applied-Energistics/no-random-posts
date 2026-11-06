# No Random Posts

WordPress plugin that disables core’s random content URLs (`/random/`, and the author, term, and post type variants) for every post type. There are no settings and nothing is stored.

The user-facing description is in [readme.txt](readme.txt).

## Tests

The tests mock the WordPress hook API and do not need a database.

```bash
composer install
composer test
```
