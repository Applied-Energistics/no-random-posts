# No Random Posts

Open `/random/` on a WordPress 7.2 site and you get sent somewhere else. The response is a 302. The somewhere else is a public post WordPress chose at random.

You did not switch this on. The update does. A site that has sat there quietly for years wakes up with a new public address.

Plenty of plugins register invoices, applications, or notes as a public type with an archive. The new flag that would keep them out is called `randomable`. You have never set it, because you have never seen it. One of those records can be the page at the end of the redirect. Google will follow it. So will the chatbots.

Activate No Random Posts and those addresses 404. A page you actually named random still loads. There is no settings screen. You do not spend the night before the update opening every post type to check a box.

WordPress finds that post by picking a random row. A bigger table makes that slower, and a bot can sit on `/random/` all day. This plugin never runs that query.

Nothing is saved. On WordPress older than 7.2 the plugin does not crash, and it leaves every other URL alone.

The same words are in [readme.txt](readme.txt). The dev note is linked from there.

## Tests

The tests pretend WordPress is there. They do not need a database.

```bash
composer install
composer test
```
