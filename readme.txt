=== No Random Posts ===
Contributors: jonoaldersonwp
Tags: seo, privacy, redirects, permalinks
Requires at least: 4.7
Tested up to: 7.1
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Disables WordPress random content URLs such as /random/ for every post type. No settings.

== Description ==

WordPress 7.2 adds public URLs that 302 to a randomly chosen published post. With the default base and pretty permalinks, that includes:

* `/random/` for any eligible post.
* `/random/category/news/` and the same pattern for every public taxonomy.
* `/random/author/name/` for a post by that author.
* `/random/{post-type}/` for a custom post type.

A post type is included when it is publicly queryable and has an archive, unless it was registered with `randomable` set to false. Custom post types are often registered without that argument, so content that was never linked (invoices, for example) can be selected and exposed. The redirect URL can also be stored as its own URL by search engines and LLMs. Core sends `X-Robots-Tag: noindex` on the redirect response. That header does not stop the destination being discovered, and it is not a noindex control for the content itself.

This plugin turns the feature off for every post type. There is no setting, no admin screen, and nothing stored.

It returns false from the `wp_enable_random_content_redirect` filter, which is the switch core documents. It also removes the random rewrite rules (including rules already saved in the rewrite option), the `random` query variable, and the callbacks that run the redirect. A post type registered before or after the plugin loads cannot opt back in by leaving `randomable` unset or setting it to true.

A page, post, or term whose slug is actually `random` still loads. Only the random-redirect endpoints are removed. If nothing else owns that path, the request stays a 404.

On WordPress versions that do not have the feature yet, the plugin does not change those requests: `/random/` is already a 404 unless a real route uses it. The 7.2 APIs are not required. Nothing is written to the database, and there is nothing to delete on uninstall.

The behaviour follows the WordPress 7.2 dev note published on 5 November 2026: https://make.wordpress.org/core/2026/11/05/your-content-is-going-to-get-more-random/

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/`, or install the zip from the Plugins screen.
2. Activate No Random Posts.

Permalinks do not need to be saved. Cached rewrite rules are filtered when WordPress reads them.

== Frequently Asked Questions ==

= Are there any settings? =

No. The plugin is on for every post type as soon as it is active. There is no per-type option.

= Will /random/ return 404? =

Yes, unless that path is already a real page, post, term, or other existing route. A page with the slug `random` still works. The same is true for a post or term with that slug at its own URL, and for translated or custom random bases such as `/zufall/`.

= Does it work before WordPress 7.2? =

Yes. Older versions do not have the endpoints. The plugin does not fatal, and it does not change any other URL.

= Does it store any data? =

No. It does not add options, tables, or files, and it does not send email or telemetry.

== Changelog ==

= 1.0.0 =
* Initial release.
