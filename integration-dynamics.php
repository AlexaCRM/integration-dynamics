<?php
/*
 * Plugin Name: Dynamics 365 Integration
 * Plugin URI: https://wordpress.org/plugins/integration-dynamics/
 * Description: The easiest way to connect Dynamics 365 and Dynamics CRM with WordPress.
 * Version: 1.2.4
 * Author: AlexaCRM
 * Author URI: https://alexacrm.com
 * Text Domain: integration-dynamics
 * Domain Path: /languages
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'WORDPRESSCRM_DIR', __DIR__ );
define( 'WORDPRESSCRM_STORAGE', WORDPRESSCRM_DIR . '/storage' );
define( 'WORDPRESSCRM_VERSION', '1.2.4' );

require_once __DIR__ . '/vendor/autoload.php'; // Composer autoloader

/**
 * Stop further initialization if the storage is not writable.
 */
if ( !is_writable( WORDPRESSCRM_STORAGE ) ) {
    add_action( 'admin_notices', function() {
        $screen = get_current_screen();
        if ( $screen->base === 'plugins' ) {
            ?>
            <div class="notice notice-error">
                <p>
                    <?php printf( __( 'Dynamics 365 Integration detected that <code>%s</code> is not writable by the web server. Please fix it to complete the installation.', 'integration-dynamics' ), WORDPRESSCRM_STORAGE ); ?>
                </p>
            </div>
            <?php
        }
    } );
    return;
}

$logger = new \Monolog\Logger( 'wpcrm' );
$logLevel = WP_DEBUG? \Monolog\Logger::INFO : \Monolog\Logger::NOTICE;
$logLevel = get_option( 'wpcrm_log_level', $logLevel );
if ( defined( 'WORDPRESSCRM_LOG_LEVEL' ) ) {
    $logLevel = WORDPRESSCRM_LOG_LEVEL;
}
define( 'WORDPRESSCRM_EFFECTIVE_LOG_LEVEL', $logLevel );
$logStream = new \Monolog\Handler\RotatingFileHandler( WORDPRESSCRM_STORAGE . '/integration-dynamics.log', 3, $logLevel );
$logger->pushHandler( $logStream );

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
                    <?php printf( __( 'Dynamics 365 Integration detected that your environment has PHP %1$s. The plugin requires at least PHP %2$s to work. Please upgrade your PHP installation to fully enable the plugin.', 'integration-dynamics' ), phpversion(), '5.4' ); ?>
                </p>
            </div>
            <?php
        }
    } );
    return;
}

/**
 * Check whether cURL is installed.
 */
if ( !function_exists( 'curl_version' ) ) {
    $logger->critical( 'cURL is not installed. Cannot proceed further.' );

    add_action( 'admin_notices', function() {
        $screen = get_current_screen();
        if ( $screen->base === 'plugins' ) {
            ?>
            <div class="notice notice-error">
                <p>
                    <?php _e( 'cURL, a PHP extension, is not installed. <strong>Dynamics 365 Integration</strong> requires cURL to work properly.', 'integration-dynamics' ); ?>
                </p>
            </div>
            <?php
        }
    } );

    return;
}

// Run migrations
register_activation_hook( __FILE__, function() {
    $update = new \AlexaCRM\WordpressCRM\Update();
    $update->updateDeprecatedOptions();
    $update->updateDataBoundPages();
    $update->updateDataBinding();
} );

require_once __DIR__ . '/core.php';
