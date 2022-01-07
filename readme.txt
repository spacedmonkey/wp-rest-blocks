=== REST API blocks ===
Contributors: spacedmonkey
Donate link: https://github.com/sponsors/spacedmonkey
Tags: blocks, gutenberg, api, wp-json, rest-api
Requires at least: 5.5
Tested up to: 5.9
Requires PHP: 7.0.0
Stable tag: 0.5.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

Add gutenberg blocks data into the post / page REST API endpoints.

== Description ==

A simple plugin to add block data in json format into the rest api. Once installed, there will be two new fields added to the rest api, `has_blocks` and `blocks`.

An example of output, can be found in the screenshots.

=== Technical Notes ===

* Requires PHP 5.6+.
* Requires WordPress 5.5+.
* Issues and Pull requests welcome on the GitHub repository: https://github.com/spacedmonkey/wp-rest-blocks

== Installation ==

### Using The WordPress Dashboard

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'wp-rest-blocks'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

### Uploading in WordPress Dashboard

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `wp-rest-blocks.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

### Using FTP
1. Download `wp-rest-blocks.zip`
2. Extract the `wp-rest-blocks` directory to your computer
3. Upload the `wp-rest-blocks` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard

== Screenshots ==

1. Add fields to the rest api.

== Changelog ==

= 0.5.0 =
* Add support for new post types added in WordPress 5.9.

= 0.4.0 =
* Added support for block based widget, added in WordPress 5.8. Block data is added to the /wp/v2/widgets endpoint.

= 0.3.2 =
* Update translations

= 0.3.1 =
* Hot fix.

= 0.3.0 =
* Improve support for block that have attributes that use query source type.
* Improve error handling for those that install this plugin without using composer.

= 0.2.1 =
* Update dependency.

= 0.2.0 =
* Breaking change. Field names have changed and required WordPress 5.5+

= 0.1.0 =
* First version.
