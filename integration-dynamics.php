<?php
/*
 * Plugin Name: Dynamics CRM Integration
 * Plugin URI: https://wordpress.org/plugins/integration-dynamics/
 * Description: The easiest way to connect Dynamics CRM with WordPress.
 * Version: 1.1.22
 * Author: AlexaCRM
 * Author URI: http://alexacrm.com
 * Text Domain: integration-dynamics
 * Domain Path: /languages
 */

use AlexaCRM\WordpressCRM\Log;
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

$logSeverityLevel = WP_DEBUG? Log::LOG_ALL : Log::LOG_FAULTS;
$logger = new Log( WORDPRESSCRM_STORAGE, $logSeverityLevel );

/**
 * Checking for the current PHP version.
 * We support 5.4+
 */
if ( version_compare( phpversion(), '5.4', '<' ) ) {
    $logger->critical( 'PHP version is less than 5.4. Cannot proceed further.', array( 'phpversion' => phpversion() ) );

    add_action( 'admin_notices', function() {
        $screen = get_current_screen();
        if ( $screen->base === 'plugins' ) {
            ?>
            <div class="notice notice-error">
                <p>
                    <?php printf( __( 'Dynamics CRM Integration detected that your environment has PHP %1$s. The plugin requires at least PHP %2$s to work. Please upgrade your PHP installation to fully enable the plugin.', 'integration-dynamics' ), phpversion(), '5.4' ); ?>
                </p>
            </div>
            <?php
        }
    } );
    return;
}

load_plugin_textdomain( 'integration-dynamics', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

// run migrations
require_once __DIR__ . '/update.php';

// start initializing the plugin

add_action( 'wp_ajax_wpcrm_log', function() use ( $logger ) {
    header( 'Content-Type: text/plain' );
    $logger->info( 'Displaying log.' );
    echo file_get_contents( $logger->logTarget );
    exit();
} );

add_action( 'init', function() {
    if ( !session_id() ) {
        session_start();
    }
}, 0 );

$pluginInstance = new Plugin();
$pluginInstance->init(  $logger );

add_action( 'admin_init', function() use ( $pluginInstance ) {
    $pluginInstance->version = get_plugin_data( __FILE__ )['Version'];
} );

/**
 * Returns the main plugin object.
 *
 * @return Plugin
 */
function ACRM() {
    global $pluginInstance;

    return $pluginInstance;
}

/**
 * Returns the CRM Toolkit object.
 *
 * @return \AlexaCRM\CRMToolkit\Client
 */
function ASDK() {
    return ACRM()->sdk;
}

/**
 * Forces a JavaScript redirect when headers have been already sent.
 *
 * Execution is halted after calling this method.
 *
 * @param string $location
 */
function wordpresscrm_javascript_redirect( $location = null ) {
    if ( !headers_sent() ) {
        wp_redirect( $location );
        exit();
    }

    /*
     * Redirect using front-end JavaScript if the headers have already been sent.
     */
    ?>
    <script type="text/javascript">
        <!--
        window.location = <?php echo ( $location ) ? json_encode( $location ) : 'window.location.href'; ?>;
        //-->
    </script>
    <?php
    exit();
}
