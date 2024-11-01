=== Wp Ajax Tree ===
Contributors: remm
Donate link: 
Tags: widget, ajax, category, tree
Requires at least: 2.8
Tested up to: 3.0.1
Stable tag: 0.2

This plugin provide a widget that display a category and pages tree on your sidebar, using ajax to collapse categories.

== Description ==
This plugin provide widget that display a list with categories and posts in your sidebar.
Widget can save the opened categories, and use cache.
== Installation ==

1. Unzip plugin archive.
1. Copy `wp-ajax-tree` directory to your wordpress plugins dir `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Now you can use ajax-tree-widget from 'Widget' menu or call it from your template by using this code: `<? if(function_exists('wp_ajax_tree_widget')) wp_ajax_tree_widget(); ?>`

== Frequently Asked Questions ==
= What about foo bar? =
You can contact with me.

== Changelog ==
= 0.2 =
* Code cleaning
* Show an loading image while load.
* Save the widget opened categories.
