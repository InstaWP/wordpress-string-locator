=== String locator ===
Contributors: Clorith
Author URI: http://www.clorith.net
Plugin URI: http://wordpress.org/plugins/string-locator/
Donate link: https://www.paypal.me/clorith
Tags: theme, plugin, text, search, find, editor, syntax, highlight
Requires at least: 4.9
Tested up to: 5.4
Stable tag: 2.4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Find and edit code or texts in your themes and plugins

== Description ==

When working on themes and plugins you often notice a piece of text that appears hardcoded into the files, you need to modify it, but you don't know what theme or plugin it's in, and certainly not which individual file to look in.

Easily search through your themes, plugins or even WordPress core and be presented with a list of files, the matched text and what line of the file matched your search.
You can then quickly make edits directly in your browser by clicking the link from the search results.

By default a consistency check is performed when making edits to files, this will look for inconsistencies with braces, brackets and parenthesis that are often accidentally left in.
This drastically reduces the risk of breaking your site when making edits, but is in no way an absolute guarantee.


== Frequently asked questions ==

= Will Smart-Scan guarantee my site is safe when making edits? =
Although it will do it's best at detecting incorrect usage of the commonly used symbols (parenthesis, brackets and braces), there is no guarantee every possible error is detected. The best safe guard is to keep consistent backups of your site (even when not making edits).

As of version 1.6, the plugin will check your site health after performing an edit. If the site is returning a site breaking error code, we'll revert to the previous version of the file.

= My search is failing and I am told that my search is an invalid pattern =
This error is only related to regex searches, and is based off how PHP reads your regex string.

When writing your search string, make sure to wrap your search in forward slashes (`/`), directly followed by any modifiers like case insensitive (`i`) that you may want to use.


== Screenshots ==

1. Searching WordPress for the string `hello dolly`.
2. Search screen when editing is disabled.
3. Having clicked the link for one of the results and being taken to the editor in the browser.
4. Smart-Scan has detected an inconsistency in the use of braces.

== Changelog ==

= 2.4.1 =
* Fixed case-sensitive class call, apparently not all PHP versions are equal in how this is treated.

= 2.4.0 =
* Updated the editor screen, to a design which more closely adheres to the WordPress editor styles.
* Added support for searching files, even if you are not able to edit them.
* Added support for jumping to not just line number, but also location inside that line.
* Added alternative to disable loopback checks when saving changes.
* Improved performance by using transients instead of option entries (lower memory usage overall).
* Improved handling of errors with links to some documentation when available.
* Improved the amount of details about the current file that are shown in the editor.
* Fixed the search results table to look like a normal table when restoring a search.

= Older entries =
See changelog.txt for the version history
