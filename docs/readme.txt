=== String locator ===
Contributors: InstaWP, Clorith
Author URI: https://instawp.com/?utm_source=sl_plugin_author
Plugin URI: http://wordpress.org/plugins/string-locator/
Tags: text, search, find, syntax, highlight
Requires at least: 4.9
Tested up to: 6.5
Stable tag: 2.6.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Find and edit code or texts in your themes and plugins

== Description ==

When working on themes and plugins you often notice a piece of text that appears hardcoded into the files, you need to modify it, but you don't know what theme or plugin it's in, and certainly not which individual file to look in.

Easily search through your themes, plugins or even WordPress core and be presented with a list of files, the matched text and what line of the file matched your search.
You can then quickly make edits directly in your browser by clicking the link from the search results.

By default a consistency check is performed when making edits to files, this will look for inconsistencies with braces, brackets and parenthesis that are often accidentally left in.
This drastically reduces the risk of breaking your site when making edits, but is in no way an absolute guarantee.

Create a replica of your live site a.k.a - [WordPress Staging](https://wordpress.org/plugins/instawp-connect) site before testing substitutions. 


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

= 2.6.5 (2024-03-12) =
* Verified compatibility with WordPress 6.5

= 2.6.4 (2024-03-12) =
* Verified compatibility with WordPress 6.4

= 2.6.3 (2023-08-10) =
* CSS/JS Loading bug fixes

= 2.6.2 (2023-08-10) =
* Added InstaWP integration.
* WP ORG Support ticket fixes.

= 2.6.1 (2022-11-02) =
* Fixed a bug causing certain setups to be unable to perform searches when editing would also be unavailable.
* Fixed a bug causing certain plugins to prevent the search results list from being displayed properly.
* Verified compatibility with WordPress 6.1

= 2.6.0 (2022-07-20) =
* Added database search feature.
* Added tools for quickly replacing data in the search results.
* Added many more filters and actions.
* Added hardening of file path checks.
* Removed one-time donation notice.
* Removed jQuery dependency in favor of vanilla JavaScript code.
* Separated search class into a base class for extenders.
* Fixed bug with code viewer sizes when resizing your window.
* Fixed bug in the list view if special characters were in the search string.
* Fixed a bug where RegEx search validation may have a false positive check for invalid patterns.
* Fixed missing translator function if Javascript is missing.
* Improved capability checks for displaying the search interface when editing is disabled.

= Older entries =
See changelog.txt for the version history.
