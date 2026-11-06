=== No Random Posts ===
Contributors: jonoaldersonwp
Tags: seo, privacy, redirects, permalinks
Requires at least: 4.7
Tested up to: 7.1
Stable tag: 1.0.1
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turns off WordPress /random/ URLs before they can surface a post you never meant anyone to find. No settings.

== Description ==

WordPress 7.2 is about to add `/random/` URLs. A request 302s to a random public post. This is on for every site. There is no opt-in.

That can surface invoices, drafts-of-a-sort, and other types people never meant to be found. Custom post types are often registered without careful public flags. If a type is publicly queryable and has an archive, it is included unless someone set `randomable` to false. Core has no noindex for the content itself. Search engines and LLMs can keep the redirect. The `X-Robots-Tag: noindex` header on that 302 does not stop the destination being discovered.

No Random Posts turns those URLs off, for every post type, with no settings. A real page, post, or term whose slug is `random` still works. If nothing else owns the path, the request stays a 404.

With the default base and pretty permalinks, the new URLs are:

* `/random/` for any eligible post.
* `/random/category/news/` and the same pattern for every public taxonomy.
* `/random/author/name/` for a post by that author.
* `/random/{post-type}/` for a custom post type.

The same applies to a translated or custom base, such as `/zufall/`.

There is a further cost. WordPress picks the post with `ORDER BY RAND()` (or the same class of query). The cost grows with the number of rows. Bots that request `/random/` over and over can turn that into a load problem. This plugin 404s the URL before that query runs.

You do not get a settings screen, and nothing is stored. You do not need to resave permalinks. A post type registered before or after the plugin loads cannot opt back in.

On older WordPress, nothing else changes. `/random/` is already a 404 unless a real route uses it. The plugin does not fatal when the 7.2 APIs are missing.

Details of the feature are in the WordPress 7.2 dev note from 5 November 2026: https://make.wordpress.org/core/2026/11/05/your-content-is-going-to-get-more-random/

== Installation ==

1. Upload the plugin to `/wp-content/plugins/`, or install the zip from the Plugins screen.
2. Activate No Random Posts.

That is the whole setup. Permalinks do not need saving.

== Frequently Asked Questions ==

= Are there any settings? =

No. It is on for every post type as soon as you activate it.

= Will /random/ return 404? =

Yes, unless that path is already a real page, post, term, or other route. A page with the slug `random` still works. So does a post or term with that slug at its own URL.

= Does it work before WordPress 7.2? =

Yes. Older versions do not have these URLs. The plugin does not fatal, and it does not change any other URL.

= Does it store any data? =

No. No options, no tables, and no files of its own. It does not send email or telemetry. There is nothing to delete when you uninstall it.

== Changelog ==

= 1.0.1 =
* Readme now leads with content leakage and unexpected endpoints.

= 1.0.0 =
* Initial release.
