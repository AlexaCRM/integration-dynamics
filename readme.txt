=== Integration for Dynamics ===
Contributors: alexacrm, georgedude, wizardist
Tags: crm, dynamics crm, forms, integration, contact form, shortcode
Requires at least: 4.5
Tested up to: 4.6.1
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The easiest way to connect Dynamics CRM with WordPress.

== Description ==
This plugin directly connects WordPress and Microsoft Dynamics CRM creating powerful portal solutions for your business.

Features:

* Support for CRM Online and CRM On-Premises 2011, 2013, 2015, 2016.
* Design forms in the CRM and insert them on your site with a form shortcode.
* Write data from the forms directly to Dynamics CRM.
* Collect leads, contact requests, support queries and much more without any coding.
* Display records using Dynamics CRM views with a shortcode. GIve your customers direct access to product catalogs, event lists, knowledge base articles.
* Bind WordPress posts and pages to Dynamics CRM records. Build a customized record view in WordPress like product information sheets
* Support for entity images and attached images with dynamic resizing and caching.
* Extensible through WordPress [actions and filters](https://codex.wordpress.org/Plugin_API).

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/integration-dynamics` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Admin->Dynamics CRM screen to configure the plugin

== Changelog ==

= 1.1.7 =
* Search in Lookup Dialog using `like` operator
* Fix record order in Lookup Dialog

= 1.1.6 =
* Fixed 404 behaviour for data-bound pages
* Improved view shortcode performance
* Internal: inline documentation, better support for non-standard WP setups

= 1.1.5 =
* Display organization name in the plugin connection status bar
* Internal: more strings available for translation

= 1.1.4 =
* Use jQuery.validate for form validation
* Internal: make About tab extensible
* Internal: plugin building enhancements

= 1.1.3 =
* Annotation images caching and resizing
* Enhancements in the admin section
* Internal: automated plugin build process

= 1.1.2 =
* Entity metadata is now cached persistently
* Fixed translations
