<?php
/*
 * Plugin Name: Dynamics CRM Integration
 * Plugin URI: https://wordpress.org/plugins/integration-dynamics/
 * Description: The easiest way to connect Dynamics CRM with WordPress.
 * Version: 1.1.20
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

/*
 * Run the plugin
 */
ACRM()->init();
