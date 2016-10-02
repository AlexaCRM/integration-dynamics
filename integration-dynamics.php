<?php
/*
 * Plugin Name: Dynamics CRM Integration
 * Plugin URI: https://wordpress.org/plugins/integration-dynamics/
 * Description: The easiest way to connect Dynamics CRM with WordPress.
 * Version: 1.1.9
 * Author: AlexaCRM
 * Author URI: http://alexacrm.com
 * Text Domain: integration-dynamics
 * Domain Path: /languages
 */

use AlexaCRM\WordpressCRM\Plugin;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'WORDPRESSCRM_DIR', __DIR__ );
define( 'WORDPRESSCRM_STORAGE', WORDPRESSCRM_DIR . '/storage' );

// register autoloaders
spl_autoload_register( function ( $className ) {
    $namespacePrefix = 'AlexaCRM\\WordpressCRM\\';

    $baseDirectory = __DIR__ . '/src/';

    $namespacePrefixLength = strlen( $namespacePrefix );
    if ( strncmp( $namespacePrefix, $className, $namespacePrefixLength ) !== 0 ) {
        return;
    }

    $relativeClassName = substr( $className, $namespacePrefixLength );

    $classFilename = $baseDirectory . str_replace( '\\', '/', $relativeClassName ) . '.php';

    if ( file_exists( $classFilename ) ) {
        require $classFilename;
    }
} );

require_once __DIR__ . '/libraries/php-crm-toolkit/init.php'; // CRM Toolkit for PHP autoloader

load_plugin_textdomain( 'integration-dynamics', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

// run migrations
require_once __DIR__ . '/update.php';

/**
 * Returns the only instance of WordpressCRM plugin container
 *
 * @return Plugin
 */
function ACRM() {
    return Plugin::instance();
}

/**
 * @return \AlexaCRM\CRMToolkit\Client
 */
function ASDK() {
    return Plugin::instance()->sdk;
}

/**
 * Merge user defined arguments into defaults array.
 *
 * This function is used throughout wordpresscrm to allow for either a string or array
 * to be merged into another array. It is identical to wp_parse_args() except
 * it allows for arguments to be passively or aggressively filtered using the
 * optional $filter_key parameter.
 *
 * @param string|array $args Value to merge with $defaults
 * @param array $defaults Array that serves as the defaults.
 * @param string $filter_key String to key the filters from
 * @return array Merged user defined values with defaults.
 */
function wordpresscrm_parse_args( $args, $defaults = array(), $filter_key = '' ) {

    // Setup a temporary array from $args
    if ( is_object( $args ) ) {
        $r = get_object_vars( $args );
    } elseif ( is_array( $args ) ) {
        $r =& $args;
    } else {
        wp_parse_str( $args, $r );
    }

    // Passively filter the args before the parse
    if ( !empty( $filter_key ) ) {
        $r = apply_filters( 'wordpresscrm_before_' . $filter_key . '_parse_args', $r );
    }

    // Parse
    if ( is_array( $defaults ) && !empty( $defaults ) ) {
        $r = array_merge( $defaults, $r );
    }

    // Aggressively filter the args after the parse
    if ( !empty( $filter_key ) ) {
        $r = apply_filters( 'wordpresscrm_after_' . $filter_key . '_parse_args', $r );
    }

    // Return the parsed results
    return $r;
}

/*
 * Run the plugin
 */
ACRM()->init();
