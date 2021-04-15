<?php
/*
 * Plugin Name: Dynamics 365 Integration
 * Plugin URI: https://wordpress.org/plugins/integration-dynamics/
 * Description: The easiest way to connect Dynamics 365 and Dynamics CRM with WordPress.
 * Version: 1.2.30
 * Author: AlexaCRM
 * Author URI: https://alexacrm.com
 * Text Domain: integration-dynamics
 * Domain Path: /languages
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'WORDPRESSCRM_VERSION', '1.2.30' );

define( 'WORDPRESSCRM_DIR', __DIR__ );

$wpUploadDir = wp_upload_dir();
$wpcrmStorageDir = null;
if ( $wpUploadDir['error'] === false ) {
    $wpcrmStorageDir = $wpUploadDir['basedir'] . '/wpcrm-storage';

    /**
     * Stop further initialization if the storage is not writable.
     */
    if ( !wp_mkdir_p( $wpcrmStorageDir ) || !is_writable( $wpcrmStorageDir ) ) {
        add_action( 'admin_notices', function() use ( $wpcrmStorageDir ) {
            $screen = get_current_screen();
            if ( $screen->base === 'plugins' ) {
                ?>
                <div class="notice notice-error">
                    <p>
                        <?php printf( __( 'Dynamics 365 Integration detected that <code>%s</code> is not writable by the web server. Please fix it to complete the installation.', 'integration-dynamics' ), $wpcrmStorageDir ); ?>
                    </p>
                </div>
                <?php
            }
        } );

        return;
    }
}

define( 'WORDPRESSCRM_STORAGE', $wpcrmStorageDir );

require_once __DIR__ . '/vendor/autoload.php'; // Composer autoloader

function wpcrm_log_processor_sanitizer( $record ) {
    if ( array_key_exists( 'request', $record['context'] ) ) {
        $record['context']['request'] = preg_replace( '~<o:Password\s+Type=".*?">.*?</o:Password>~', '<o:Password/>', $record['context']['request'] );
        $record['context']['request'] = preg_replace( '~<default:CipherValue>.*?</default:CipherValue>~', '<default:CipherValue/>', $record['context']['request'] );
    }

    return $record;
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
$logger->pushProcessor( 'wpcrm_log_processor_sanitizer' );

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
register_activation_hook( __FILE__, function() use ( $wpcrmStorageDir ) {
    $update = new \AlexaCRM\WordpressCRM\Update();
    $update->updateDeprecatedOptions();
    $update->updateDataBoundPages();
    $update->updateDataBinding();

    function removeDir( $target ) {
        try {
            $iterator = new RecursiveDirectoryIterator( $target, RecursiveDirectoryIterator::SKIP_DOTS );
            $fileIterator = new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::CHILD_FIRST );
            foreach ( $fileIterator as $file ) {
                /** @var SplFileInfo $file */
                if ( is_dir( $file ) ) {
                    rmdir( $file->getRealPath() );
                    continue;
                }

                unlink( $file->getRealPath() );
            }

            rmdir( $target );
        } catch ( \Exception $e ) {} // Silence is golden.
    }

    // Drop the cache.
    if ( is_dir( $wpcrmStorageDir . '/cache' ) ) {
        removeDir( $wpcrmStorageDir . '/cache' );
    }

    // add .htaccess to prevent access to the storage directory
    if ( !file_exists( $wpcrmStorageDir . '/.htaccess' ) ) {
        $htaccessContent = "Order Deny,Allow\nDeny from all\nAllow from 127.0.0.1\n";
        @file_put_contents( $wpcrmStorageDir . '/.htaccess', $htaccessContent );
    }
} );

require_once __DIR__ . '/core.php';
