=== No Random Posts ===
Contributors: jonoaldersonwp
Tags: seo, privacy, redirects, permalinks
Requires at least: 4.7
Tested up to: 7.1
Stable tag: 1.0.1
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress 7.2 will answer /random/ with a public post. This stops that. No settings.

== Description ==

Open `/random/` on a WordPress 7.2 site and you get sent somewhere else. The response is a 302. The somewhere else is a public post WordPress chose at random.

You did not switch this on. The update does. A site that has sat there quietly for years wakes up with a new public address.

Plenty of plugins register invoices, applications, or notes as a public type with an archive. The new flag that would keep them out is called `randomable`. You have never set it, because you have never seen it. One of those records can be the page at the end of the redirect. Google will follow it. So will the chatbots.

Activate No Random Posts and those addresses 404. A page you actually named random still loads. There is no settings screen. You do not spend the night before the update opening every post type to check a box.

WordPress finds that post by picking a random row. A bigger table makes that slower, and a bot can sit on `/random/` all day. This plugin never runs that query.

Nothing is saved. On WordPress older than 7.2 the plugin does not crash, and it leaves every other URL alone. `/random/` was already a 404 if you had no page by that name.

The feature is written up in the WordPress 7.2 dev note, 5 November 2026: https://make.wordpress.org/core/2026/11/05/your-content-is-going-to-get-more-random/

== Installation ==

1. Upload the plugin to `/wp-content/plugins/`, or install the zip on the Plugins screen.
2. Activate No Random Posts.

You do not resave permalinks.

== Frequently Asked Questions ==

= Are there any settings? =

No. Turn it on. That is the whole job.

= Will /random/ 404? =

Yes, unless you already have a real page, post, or term at that address. A page named random still loads.

= Does it work before WordPress 7.2? =

Yes. Older WordPress does not have these URLs. The plugin will not crash, and it will not change anything else.

= Does it store any data? =

No. No options, no tables, nothing of its own to delete later. It does not send email.

== Changelog ==

= 1.0.1 =
* Readme now says what /random/ does to a site you already have.

= 1.0.0 =
* First release.
