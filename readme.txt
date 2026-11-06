=== No Random Posts ===
Contributors: jonoaldersonwp
Tags: seo, privacy, redirects, permalinks
Requires at least: 4.7
Tested up to: 7.1
Stable tag: 1.0.2
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Visit /random/ on your website, and WordPress will issue a 302 redirect to a randomly selected public post.

== Description ==

Visit `/random/` on your website, and WordPress will issue a 302 redirect to a randomly selected public post.

That might sound harmless, but there are a few reasons that this can be a problem.

For starters, WordPress doesn't necessarily limit its selection to ordinary blog posts. Plugins frequently register custom post types for things like invoices, applications, documents, or internal records. If those types are publicly accessible and have archives, they may also be eligible for random selection. There's a `randomable` setting to control this, but unless you've explicitly configured it, you might be exposing content in ways you hadn't anticipated.

Then there's the SEO problem. Search engines and AI crawlers can discover `/random/`, follow its redirects, and repeatedly encounter different content at the same address. That's hardly ideal if you're trying to maintain a predictable, well-structured website with sensible crawling and indexing behaviour.

And, of course, there's a performance consideration. Selecting a random post requires a database query, which can become increasingly expensive as your content grows. An enthusiastic crawler repeatedly requesting `/random/` can generate a surprising amount of unnecessary work.

No Random Posts disables this behaviour entirely.

Activate the plugin, and requests to `/random/` return a proper 404 response. No redirects, no random database queries, and no unexpected exposure of content through this mechanism.

It's deliberately minimal. There are no settings to configure, no database changes, and no need to audit every custom post type to work out whether it's eligible. It also takes care not to interfere with legitimate content, so if you've actually created a page called `random`, that'll continue to work normally. The same is true of a post or term that already owns that address. If nothing else owns the path, the request stays a 404.

The plugin is also backwards-compatible with WordPress versions before 7.2, where it safely leaves existing behaviour alone.

One small plugin, one less piece of WordPress weirdness to worry about.

The feature is described in the core dev note from 5 November 2026: https://make.wordpress.org/core/2026/11/05/your-content-is-going-to-get-more-random/

== Installation ==

1. Upload the plugin to `/wp-content/plugins/`, or install the zip from the Plugins screen.
2. Activate No Random Posts.

That's the whole job. You don't need to resave permalinks.

== Frequently Asked Questions ==

= Are there any settings? =

No. There is nothing to configure. Activate the plugin and you're done.

= Will /random/ return 404? =

Yes, unless a real page, post, or term already owns that address. A page you've actually called `random` still works.

= Does it work before WordPress 7.2? =

Yes. Older versions don't have these URLs. The plugin safely leaves existing behaviour alone, and it doesn't crash.

= Does it store any data? =

No. There are no database changes, no options, and nothing of its own to delete later. It doesn't send email.

== Changelog ==

= 1.0.2 =
* Readme is the explanation of what /random/ does.

= 1.0.1 =
* Readme now explains what /random/ does on a site you already have.

= 1.0.0 =
* First release.
