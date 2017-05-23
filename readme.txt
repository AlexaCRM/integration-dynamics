=== Dynamics 365 Integration ===
Contributors: alexacrm, georgedude, wizardist
Tags: contact form, CRM, dynamics crm, dynamics 365, form, integration, leads, membership, portal, shortcode
Requires at least: 4.4
Tested up to: 4.7.4
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The easiest way to connect Dynamics 365 or Dynamics CRM with WordPress.

== Description ==

This plugin directly connects WordPress and Microsoft Dynamics 365 or Dynamics CRM, creating powerful portal solutions for your business.

Features:

* Support for Dynamics 365 Online, Dynamics 365 On-Premises, and Dynamics CRM 2011, 2013, 2015, and 2016.
* Design forms in the Dynamics 365 / CRM and insert them on your site with a form shortcode.
* Write data from the forms directly to Dynamics 365 / CRM.
* Collect leads, contact requests, support queries and much more without any coding.
* Display records using Dynamics 365 / CRM views with a shortcode. Give your customers direct access to product catalogs, event lists, knowledge base articles.
* Bind WordPress posts and pages to Dynamics 365 / CRM records. Build a customized record view in WordPress like product information sheets
* Support for entity images and attached images with dynamic resizing and caching.
* Extensible through WordPress [actions and filters](https://codex.wordpress.org/Plugin_API).

= Minimum PHP version =

This plugin requires at least PHP 5.4. Versions 5.6 or 7.x are expected to have full compatibility.

= WARNING =

For this plugin to work, access to a working instance of Dynamics 365 Online, or Dynamics 365 / CRM On-Premises with IFD (Internet Facing Deployment) is required. Without an instance of Dynamics 365 / CRM the plugin is absolutely useless. Please, do not raise issues related to that fact. If you are curious to try, you can always sign up for a [free trial of Dynamics 365](https://www.microsoft.com/en-us/dynamics/crm-free-trial-overview.aspx).

= Reporting issues =

Development of this plugin takes place at the [GitHub repository](https://github.com/AlexaCRM/integration-dynamics). Please address all questions and issues there.

== Installation ==

Installing Dynamics 365 Integration is just like any other WordPress plugin:

* Inside of your WordPress dashboard navigate to your WordPress **Plugins** page
* In the search field enter **Dynamics CRM** or **Dynamics 365**, then click **Search Plugins**, or press Enter.
* Select **Dynamics 365 Integration** and click **Install Now**.
* Once installed, click **Activate**.
* Navigate to **Admin > Dynamics CRM** page to enter your Dynamics 365 / CRM connection details.

== Changelog ==

= 1.1.32.5 =

* Fixed: default values containing dots would not be rendered

= 1.1.32.4 =

* Fixed: prevent fatal crashes if environment doesn't meet dependency requirements

= 1.1.32.3 =

* Fixed: fatal crash if the /storage directory is not writable
* Fixed: form submissions with empty attachment field

= 1.1.32.2 =

* Fixed: display boolean controls in forms correctly
* Fixed: notifications in admin UI would not be displayed

= 1.1.32.1 =

* A more detailed message displayed if form submission has failed
* Fixed: incompatibility with some plugins (session already started)

= 1.1.32 =

* Decreased plugin size
* Added links to documentation on the configuration pages
* Misc internal fixes and optimizations

= 1.1.31 =

* Stability improvements and better error reporting

= 1.1.30 =

* Stability improvements and better error reporting

= 1.1.29 =

* Fixed: format readonly date-time fields in forms appropriately
* Fixed: plugin would break WP-CLI
* Internal: Monolog used as logging facility

= 1.1.28 =

* Show date-time fields in forms according to WordPress date format settings
* Fixed: Post editing UI crashes when plugin is not connected

= 1.1.27 =

* Sort entities alphabetically in data-binding UI
* Performance improvements in forms with lookupviews

= 1.1.26 =

* Update the JS resources
* Support for deprecated uitypes in view lookups

= 1.1.25 =

* Plugin compatible with WordPress 4.7
* Enhanced page-CRM record binding
* Code clean-up and optimization
* Entity display names in the Lookup Dialog
* Fixed: custom view/form template paths

= 1.1.24 =

* Allow customizing the error message if WordPress is not connected to the CRM.
* Fixed: Pagination links in views.
* Fixed: WordPress 4.4 is now marked as compatible with the plugin.
* Fixed: Fatal crash when connecting to CRM.

= 1.1.23 =

* Added Shortcode Wizard to quickly generate shortcodes (View and Field supported)
* Internal: code clean-up, Composer introduced to manage dependencies

= 1.1.22 =

* Add pagination for views.
* Allow specifying a target DOM selector for form messages.
* Fixed: Fatal crash on environments with PHP < 5.4.

= 1.1.21 =

* Added logging.
* Fixed: boolean fields in forms.
* Fixed: consistent form shortcode attributes.

= 1.1.20 =

* Dynamics CRM Online in the Canada region is now supported.

= 1.1.19 =

* Fixed: Proper handling of invalid certificate errors.
* Added a control to allow ignoring invalid SSL certificates.

= 1.1.18 =

* Fixed: Self-signed certificate would not allow to connect
* Fixed: Cache purging for some caching engines
* Fixed: Forms didn't have a nonce

= 1.1.17 =

* Fixed: Back-end validation would not have error messages styled correctly
* Fixed: Front-end form validator would allow emails like <example@contoso>
* Field shortcode is now not wrapped in a paragraph by default
* Fixed: first row in the view would not be linked to a data-bound page in certain scenarios

= 1.1.16 =

* Fixed: misleading message regarding inline views
* Fixed: cell width would not be set correctly for linked record fields in views
* Internal: data-binding refactoring

= 1.1.15 =

* Performance optimizations for data-bound posts and pages
* View shortcode now honors CRM View column widths in layout mode
* Fixed: Lookup Dialog would crash on some entities due to inconsistencies in Dynamics CRM metadata

= 1.1.14 =

* Hotfix: Remove debugging statements that were left out in the previous release
* No other changes in this release were made

= 1.1.13 =

* Form templates are now more compatible with Bootstrap 3 and 4
* Fixed: wrong front-end validation settings which could break validation process

= 1.1.12 =

* Lookup Dialog performance is increased through caching
* Fixed: View field of type State would not be rendered
* Fixed: Form validation messages would not honor Bootstrap styles

= 1.1.11 =

* Form shortcode now renders a cleaner output
* Make forms and views more compatible with Bootstrap styles
* Make View and Form shortcodes more extensible
* Fixed: run migrations only once

= 1.1.10 =

* Fix the broken lookup dialog (wouldn't work on most installs)

= 1.1.9 =

* CRM Online is set to be the default connection option
* Fixed: Settings link in the Plugins screen led to the wrong page
* Fixed: Default form templates wouldn't work if installed on a case-sensitive file system
* Fixed (upstream CRM Toolkit): Couldn't connect to CRM if password contained special XML characters

= 1.1.8 =

* Text domain is updated to support WordPress translations service

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
