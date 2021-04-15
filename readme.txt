=== Dynamics 365 Integration ===
Contributors: alexacrm, georgedude, wizardist
Tags: contact form, CRM, dynamics crm, dynamics 365, form, integration, leads, membership, portal, shortcode
Requires at least: 4.4
Tested up to: 5.6
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

= Documentation =

Plugin documentation is available at [docs.alexacrm.com/wpcrm/](http://docs.alexacrm.com/wpcrm/).

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

= 1.2.30 =

* Removed regional discovery service

= 1.2.29.2 =

* Allow to use WPCRM_KEY more than 32 symbols length

= 1.2.29.1 =

* Fixed caching method selection

= 1.2.29 =

* Added WPCRM_CACHE_METHOD to allow changing the caching method (off/wpcache/files)
* Fixed JS redirects in forms
* Fixed PHP 7.4 incompatibility issues

= 1.2.28 =

* Added extensibility points for Twig
* Added custom validation in Twig forms

= 1.2.27.3 =

* Maintenance release to improve logging

= 1.2.27.2 =

* Fixed language-related issues

= 1.2.27.1 =

* Fixed "name" field value in Twig forms used with existing records
* Fixed PHP notices in Twig forms

= 1.2.27 =

* Fixed Twig form submissions with the "name" field present on the form

= 1.2.26 =

* Fixed add_query() Twig filter to produce a correct result for relative URLs

= 1.2.25 =

* Fixed lookup dialog behavior in forms with multiple lookup fields

= 1.2.24 =

* Proper integration with WP proxy settings
* Fixed entity image behavior in newer Dynamics deployments

= 1.2.23 =

* Added support for global option sets in Twig
* Fixed Twig cache purging
* Various fixes for the lookup dialog

= 1.2.22 =

* Add support for money, decimal and integer fields in Twig forms

= 1.2.21 =

* Allow following the exact STS URL in federated online deployments

= 1.2.20.3 =

* Fixed: conflict with other WordPress plugins which provide a newer version of Twig
* Fixed: possible PHP notice when using Twig views

= 1.2.20.2 =

* Fixed: possible halt on unrecognized cache storage methods

= 1.2.20.1 =

* Fixed: possible fatal error during plugin activation/upgrade

= 1.2.20 =

* Dropped SQLite support in cache

= 1.2.19 =

* Allow multiple Twig forms on one page
* Add a filter to make annotation images delivery more extensible

= 1.2.18 =

* Add shortcode attribute to disable nonce check in forms
* Add referrer information to the request object for Twig templates
* Treat email addresses with capital letters as valid
* Fixed: crash in Twig if no active connection to CRM

= 1.2.17.1 =

* Fixed: crashes on some environments related to TLSv1.2 support in cURL

= 1.2.17 =

* Fix lookup handling in Twig forms

= 1.2.16.1 =

* Follow-up release for 1.2.16

= 1.2.16 =

* Default values for lookups, address composites, dropdowns, radios and checkboxes in Twig forms
* Fixed handling of of empty values in forms

= 1.2.15 =

* Fixed handling of boolean values in forms

= 1.2.14 =

* Fixed query string handling in forms (URL-encoded values in inputs and PHP notices)
* Fixed support for some Online deployments and better authentication failure handling

= 1.2.13 =

* Introduced supports for online organizations with federated Azure AD / ADFS authentication

= 1.2.12.2 =

* Fixed: potential crash when logging is performed in certain situations

= 1.2.12.1 =

* Fixed: Dynamics credentials could be leaked via logs

= 1.2.12 =

* **Important Update:** connection passwords are now encrypted. It is advised to set a WPCRM_KEY constant in wp-config.php (a base64-encoded 256-bit random key) and re-establish the connection.

= 1.2.11 =

* Fixed: connection to some IFD deployments would not work

= 1.2.10 =

* Fixed: cell width for full-width controls in Twig forms
* Fixed: required fields in Twig forms
* Fixed: hidden controls were still still displayed in Twig forms
* Fixed: legacy form shortcode attributes were treated incorrectly with extra spaces

= 1.2.9 =

* Localized forms and views
* Fixed: decimal and email handling errors in forms

= 1.2.8.1 =

* Fixed: incorrect regex rules would prevent submitting forms with Money fields

= 1.2.8 =

* List of CRM entities added to the Twig environment
* Better support for parameters and lookup substitution in Twig views
* Fixed: dropdowns in Firefox could have not behaved as intended
* Fixed: .htaccess was checked at every request instead of installation/update phase

= 1.2.7 =

* Implement Twig form lookup control as a dropdown
* Fixed: Twig form record would not be applied
* Fixed: custom persistent storage clean-up
* Fixed: check Twig form parameters better

= 1.2.6.1 =

* Fixed: destroy entity binding config if no entity selected
* Fixed: don't retrieve the bound record if no action required upon 404
* Fixed: get_post() may return null if entity binding is requested during WordPress start-up (404 check)

= 1.2.6 =

* Append form attachment to the current form record if it's an annotation
* Fixed: redirect_url in forms should refresh the page if the value is "."

= 1.2.5 =

* Allow to set currentrecord as default form values
* Storage directory moved to the WordPress uploads directory
* Fixed: Twig would not work on WPEngine due to caching issues

= 1.2.4 =

* Allow downloading CRM attachments (+ Twig function to generate an URL)
* Fixed: downloaded log file/archive would contain quotes in Firefox
* Fixed: catch exceptions during Twig form submissions and surface error messages
* Fixed: null-point reference if a post doesn't exist during binding 404 check

= 1.2.3.1 =

* Fixed: CRM errors would not be displayed after a failed form submission

= 1.2.3 =

* Allow post-submit Twig form actions for extensibility
* Fixed: setting a lookup value in a form could trigger a screen of death on submit

= 1.2.2 =

* Form captcha is now not shown to authenticated users
* Enabled Twig cache (may be disabled by setting WORDPRESSCRM_TWIG_CACHE_DISABLE constant to true)
* Allow creating custom Twig forms without a corresponding CRM form

= 1.2.1 =

* Add support for nested shortcodes in Twig templates
* DB migration is only run during plugin activation/upgrade
* Disable Twig form controls if they are not allowed for create/edit in metadata
* WordPress CA is supplied to cURL to avoid potential problems with broken system CA
* Fixed lookup submission in Twig forms
* Fixed paths in the log export ZIP

= 1.2.0.1 =

* Fixed default lookup values for the form shortcode
* Composer is now used to autoload plugin files

= 1.2 =

* New: Universal Twig templating engine that allows views, forms, fetchxml queries and more in one shortcode
* Download log files in the About section
* Proper 404 errors on bound pages

= 1.1.32.9 =

* Fixed: POST request shouldn't trigger form verification if no form data received

= 1.1.32.8 =

* Fixed: fatal errors when generation GUIDs in some situations

= 1.1.32.7 =

* Fixed: plugin could disconnect from CRM when initializing

= 1.1.32.6 =

* Fixed: issue when connecting to Dynamics 365 Online cannot be established from some geographical locations

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
