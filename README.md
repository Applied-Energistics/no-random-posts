# No Random Posts

Visit `/random/` on your website, and WordPress will issue a `302` redirect to a randomly selected public post.

That might sound harmless, but there are a few reasons that this can be a problem.

For starters, WordPress doesn't necessarily limit its selection to ordinary blog posts. Plugins frequently register custom post types for things like invoices, applications, documents, or internal records. If those types are publicly accessible and have archives, they may also be eligible for random selection. There's a `randomable` setting to control this, but unless you've explicitly configured it, you might be exposing content in ways you hadn't anticipated.

Then there's the SEO problem. Search engines and AI crawlers can discover `/random/`, follow its redirects, and repeatedly encounter different content at the same address. `/random/` can also be stuck on the end of other URLs, such as a category archive, so the crawl is not one address. That's hardly ideal if you're trying to maintain a predictable, well-structured website with sensible crawling and indexing behaviour.

And, of course, there's a performance consideration. Selecting a random post requires a database query, which can become increasingly expensive as your content grows. An enthusiastic crawler repeatedly requesting `/random/`, including `/random/` appended to other URLs, can generate a surprising amount of unnecessary work.

**No Random Posts disables this behaviour entirely.**

Activate the plugin, and requests to `/random/` return a proper `404` response. No redirects, no random database queries, and no unexpected exposure of content through this mechanism.

It's deliberately minimal. There are no settings to configure, no database changes, and no need to audit every custom post type to work out whether it's eligible. It also takes care not to interfere with legitimate content, so if you've actually created a page called `random`, that'll continue to work normally.

The plugin is also backwards-compatible with WordPress versions before 7.2, where it safely leaves existing behaviour alone.

One small plugin, one less piece of WordPress weirdness to worry about.
