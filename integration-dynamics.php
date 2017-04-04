<?php
/*
 * Plugin Name: Dynamics CRM Integration
 * Plugin URI: https://wordpress.org/plugins/integration-dynamics/
 * Description: The easiest way to connect Dynamics CRM with WordPress.
 * Version: 1.1.32
 * Author: AlexaCRM
 * Author URI: http://alexacrm.com
 * Text Domain: integration-dynamics
 * Domain Path: /languages
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'WORDPRESSCRM_DIR', __DIR__ );
define( 'WORDPRESSCRM_STORAGE', WORDPRESSCRM_DIR . '/storage' );
define( 'WORDPRESSCRM_VERSION', '1.1.32' );

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

require_once __DIR__ . '/vendor/autoload.php'; // Composer autoloader

$logger = new \Monolog\Logger( 'wpcrm' );
$logLevel = WP_DEBUG? \Monolog\Logger::INFO : \Monolog\Logger::NOTICE;
if ( defined( 'WORDPRESSCRM_LOG_LEVEL' ) ) {
    $logLevel = WORDPRESSCRM_LOG_LEVEL;
}
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
                    <?php printf( __( 'Dynamics CRM Integration detected that your environment has PHP %1$s. The plugin requires at least PHP %2$s to work. Please upgrade your PHP installation to fully enable the plugin.', 'integration-dynamics' ), phpversion(), '5.4' ); ?>
                </p>
            </div>
            <?php
        }
    } );
    return;
}

require_once __DIR__ . '/core.php';
