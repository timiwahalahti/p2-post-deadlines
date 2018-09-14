=== P2 Post Deadlines ===
Contributors: sippis
Tags: p2, deadline, deadlines
Requires at least: 4.6
Tested up to: 4.9.8
Stable tag: 1.0.0
Requires PHP: 5.2.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.html

Simple plugin to add deadlines for P2 posts and list upcoming deadlines

== Description ==

This plugin allows you to set deadline for posts and list posts with upcoming deadlines via shortcode.

Plugin is intended to be used with P2 theme, but can also work without it - only P2 spesific functionality is datepicker field added to P2 frontend post submit area. Datepicker for setting deadline is also available in classic editor and Gutenberg.

Plugin also automatically adds deadline to the end of `the_content` output if deadline is set. List of posts with upcoming deadlines are cached to transients to reduce amount of databse queries in large scale sites.

== Installation ==

Installing "P2 Post Deadlines" can be done either by searching for "P2 Post Deadlines" via the "Plugins > Add New" screen in your WordPress dashboard, or by using the following steps:

1. Download the plugin via WordPress.org
2. Upload the ZIP file through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
3. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How I can set deadline for post? =

Post deadline can be set via datepicker in classic editor or Gutenberg from meta box labeled "P2 Post deadline". If you are running P2 theme, deadline can be set from datepicker in front end post submit area also. 

= How to list posts with upcoming deadlines? =

Just use shortcode `[upcoming_post_deadlines]` somewhere in your content! If you want to change the order so that post with, add attribute `order="DESC"` to shortcode.

= Can I use my own template for shortcode? =

Yes! From plugin files copy `views/shortcode-list-upcoming-deadlines-php` to your theme root with name `p2-post-deadlines-shortcode-list-upcoming.php` and then just modify the template to suit your needs.

= My list of posts with upcoming dedline isn't updating! =

Oh snap! Plugin stores upcoming deadlines in transients to reduce amount of databse queries in large scale sites. Probably something is stuck and you should manually delete those transients from `wp_options` table or in-memory cache like Redis.

Transient are named like `p2_posts_with_deadline_YYYYMMDD_ASC/DESC`.

= Can I change the behavior of this plugin? =

Of course, but it needs some basic knowledge about PHP and WordPress hooks. This plugin does not have any settings page, only thing you can do without touching the code is chaning the post order in shortcode.

If you know your PHP and WordPress hooks, look throught `p2-post-deadlines.php` file and find what you need :)

== Changelog ==

= 1.0.0 =
* 2018-09-14
* Initial release
