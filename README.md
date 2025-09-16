fin-cli/rewrite-command
======================

Lists or flushes the site's rewrite rules, updates the permalink structure.

[![Testing](https://github.com/fin-cli/rewrite-command/actions/workflows/testing.yml/badge.svg)](https://github.com/fin-cli/rewrite-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### fin rewrite

Lists or flushes the site's rewrite rules, updates the permalink structure.

~~~
fin rewrite
~~~

See the FinPress [Rewrite API](https://codex.finpress.org/Rewrite_API) and
[FIN Rewrite](https://codex.finpress.org/Class_Reference/FIN_Rewrite) class reference.

**EXAMPLES**

    # Flush rewrite rules
    $ fin rewrite flush
    Success: Rewrite rules flushed.

    # Update permalink structure
    $ fin rewrite structure '/%year%/%monthnum%/%postname%'
    Success: Rewrite structure set.

    # List rewrite rules
    $ fin rewrite list --format=csv
    match,query,source
    ^fin-json/?$,index.php?rest_route=/,other
    ^fin-json/(.*)?,index.php?rest_route=/$matches[1],other
    category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$,index.php?category_name=$matches[1]&feed=$matches[2],category
    category/(.+?)/(feed|rdf|rss|rss2|atom)/?$,index.php?category_name=$matches[1]&feed=$matches[2],category
    category/(.+?)/embed/?$,index.php?category_name=$matches[1]&embed=true,category



### fin rewrite flush

Flushes rewrite rules.

~~~
fin rewrite flush [--hard]
~~~

Resets FinPress' rewrite rules based on registered post types, etc.

To regenerate a .htaccess file with FIN-CLI, you'll need to add the mod_rewrite module
to your fin-cli.yml or config.yml. For example:

```
apache_modules:
  - mod_rewrite
```

**OPTIONS**

	[--hard]
		Perform a hard flush - update `.htaccess` rules as well as rewrite rules in database. Works only on single site installs.

**EXAMPLES**

    $ fin rewrite flush
    Success: Rewrite rules flushed.



### fin rewrite list

Gets a list of the current rewrite rules.

~~~
fin rewrite list [--match=<url>] [--source=<source>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--match=<url>]
		Show rewrite rules matching a particular URL.

	[--source=<source>]
		Show rewrite rules from a particular source.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to match,query,source.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		---

**EXAMPLES**

    $ fin rewrite list --format=csv
    match,query,source
    ^fin-json/?$,index.php?rest_route=/,other
    ^fin-json/(.*)?,index.php?rest_route=/$matches[1],other
    category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$,index.php?category_name=$matches[1]&feed=$matches[2],category
    category/(.+?)/(feed|rdf|rss|rss2|atom)/?$,index.php?category_name=$matches[1]&feed=$matches[2],category
    category/(.+?)/embed/?$,index.php?category_name=$matches[1]&embed=true,category



### fin rewrite structure

Updates the permalink structure.

~~~
fin rewrite structure <permastruct> [--category-base=<base>] [--tag-base=<base>] [--hard]
~~~

Sets the post permalink structure to the specified pattern.

To regenerate a .htaccess file with FIN-CLI, you'll need to add
the mod_rewrite module to your [FIN-CLI config](https://make.finpress.org/cli/handbook/config/#config-files).
For example:

```
apache_modules:
  - mod_rewrite
```

**OPTIONS**

	<permastruct>
		The new permalink structure to apply.

	[--category-base=<base>]
		Set the base for category permalinks, i.e. '/category/'.

	[--tag-base=<base>]
		Set the base for tag permalinks, i.e. '/tag/'.

	[--hard]
		Perform a hard flush - update `.htaccess` rules as well as rewrite rules in database.

**EXAMPLES**

    $ fin rewrite structure '/%year%/%monthnum%/%postname%/'
    Success: Rewrite structure set.

## Installing

This package is included with FIN-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in FIN-CLI, run:

    fin package install git@github.com:fin-cli/rewrite-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out FIN-CLI's guide to contributing](https://make.finpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/fin-cli/rewrite-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/fin-cli/rewrite-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.finpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/fin-cli/rewrite-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.finpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.finpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://fin-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `fin scaffold package-readme` ([doc](https://github.com/fin-cli/scaffold-package-command#fin-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
