<?php

namespace AlexaCRM\WordpressCRM;

use AlexaCRM\CRMToolkit\Client;
use AlexaCRM\CRMToolkit\Entity\MetadataCollection;
use AlexaCRM\CRMToolkit\Settings;
use AlexaCRM\WordpressCRM\Image\AnnotationImage;
use AlexaCRM\WordpressCRM\Image\CustomImage;
use Exception;
use Symfony\Component\HttpFoundation\Request;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Core plugin object
 *
 * @package AlexaCRM\WordpressCRM
 */
final class Plugin {

    /**
     * Shortcode and option name prefix.
     */
    const PREFIX = 'msdyncrm_';

    /**
     * @var string
     */
    public $version = '';

    /**
     * Client class object
     *
     * @var Client
     */
    public $sdk = null;

    /**
     * Cache class object
     *
     * @var Cache
     */
    public $cache = null;

    /**
     * Persistent metadata storage
     *
     * @var PersistentStorage
     */
    public $metadataStorage = null;

    /**
     * Persistent CRM images storage
     *
     * @var PersistentStorage
     */
    public $imageStorage = null;

    /**
     * Logging facility.
     *
     * @var Log
     */
    public $log = null;

    /**
     * Current request.
     *
     * @var Request
     */
    public $request = null;

    /**
     * Access to templates.
     *
     * @var Template
     */
    public $template = null;

    /**
     * Plugin general and connection options (msdyncrm_options)
     *
     * @var array
     */
    public $options = null;

    /**
     * Cloning is forbidden.
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'integration-dynamics' ), $this->version );
    }

    /**
     * Unserializing instances of this class is forbidden.
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'integration-dynamics' ), $this->version );
    }

    /**
     * Initialize plugin
     *
     * @param Log $log
     */
    public function init( Log $log, Request $request ) {
        $this->log = $log;
        $this->log->info( 'Initializing Dynamics CRM Integration.' );

        $this->request = $request;

        // Include required files
        $this->includes();

        // Action to call the options update, see wordpress-crm/update.php file
        do_action( 'wordpresscrm_before_load' );

        $options       = get_option( static::PREFIX . 'options' );
        $this->options = $options;
        $this->initCache();

        if ( $options && isset( $options['connected'] ) && $options['connected'] == true ) {
            try {
                $this->initCrmConnection();
            } catch ( Exception $ex ) {
                $this->log->warning( 'Caught exception while initializing connection to CRM.', [ 'exception' => $ex ] );
                $this->options['connected'] = $options['connected'] = false;
                update_option( static::PREFIX . 'options', $options );
            }
        }

        if ( $this->sdk ) {
            new LookupDialog();
            new CustomImage();
            new AnnotationImage( $this->imageStorage );

            do_action( 'wordpresscrm_extended_includes' );

            include_once( WORDPRESSCRM_DIR . '/includes/template-shortcuts.php' );
        }

        if ( is_admin() ) {
            $this->log->info( 'Initializing admin UI.' );
            new Admin();
        }

        DataBinding::instance();

        if ( !is_admin() ) {
            add_action( 'after_setup_theme', function() {
                $this->log->info( 'Initializing shortcodes.' );

                new ShortcodeManager();
            } );

            $this->template = new Template();
        }

        // Loaded action
        do_action( 'wordpresscrm_loaded' );
    }

    /**
     * Initializes plugin cache
     */
    private function initCache() {
        if ( $this->cache instanceof Cache && $this->metadataStorage instanceof PersistentStorage
        && $this->imageStorage instanceof PersistentStorage ) {
            return;
        }

        $this->log->info( 'Initializing cache.' );

        $this->options['cache'] = [ 'server' => 'localhost', 'port' => 11211 ];
        if ( defined( 'WORDPRESSCRM_CACHESERVER' ) && defined( 'WORDPRESSCRM_CACHEPORT' ) ) {
            $this->options['cache'] = [
                'server' => WORDPRESSCRM_CACHESERVER,
                'port'   => WORDPRESSCRM_CACHEPORT,
            ];
        }

        $this->cache = new Cache( $this->options['cache'] );
        $this->metadataStorage = new PersistentStorage( 'metadata' );
        $this->imageStorage = new PersistentStorage( 'images' );
    }

    /**
     * Initializes connection to the CRM
     */
    private function initCrmConnection() {
        $options = $this->options;

        $this->log->info( 'Initializing PHP CRM Toolkit.' );

        $clientSettings = new Settings( $options );

        /*
         * Log configuration with sensitive data redacted.
         */
        $logSettings = clone $clientSettings;
        $logSettings->password = $logSettings->oauthClientId = $logSettings->oauthClientSecret = '__redacted__';
        $this->log->debug( 'PHP CRM Toolkit configuration.', array( 'settings' => $logSettings ) );
        unset( $logSettings );

        $this->sdk = new Client( $clientSettings, $this->cache, $this->log );

        $this->log->debug( 'Finished initializing PHP CRM Toolkit.' );

        // initialize Metadata storage
        $this->log->info( 'Initializing PHP CRM Toolkit Metadata storage.' );
        MetadataCollection::instance( $this->sdk )->setStorage( $this->metadataStorage );
    }

    /**
     * Retrieve prefixed WordPress option
     *
     * @param $field
     * @param mixed $default Default value
     *
     * @return mixed
     * @see Plugin::PREFIX
     */
    public function option( $field, $default = null ) {
        return get_option( static::PREFIX . $field, $default );
    }

    /**
     * Check Dynamics CRM connection status
     *
     * @return boolean TRUE if connected, FALSE if not
     */
    public function connected() {
        return ( isset( $this->options ) && isset( $this->options["connected"] ) && $this->options["connected"] == true );
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    private function includes() {
        /* Hooks */
        add_action( 'widgets_init', function () {
            $this->log->info( 'Initializing widgets' );
            do_action( 'wordpresscrm_widgets_init' );
        }, 10 );

        /**
         * Add 'Settings' link to the list of plugins
         */
        add_filter( 'plugin_action_links', function ( $links, $file ) {
            // Return normal links if not wordpresscrm
            if ( 'integration-dynamics/integration-dynamics.php' !== $file ) {
                return $links;
            }

            // New links to merge into existing links
            $new_links = [ ];

            // Settings page link
            if ( current_user_can( 'manage_options' ) ) {
                $settingsLabel         = __( 'Settings', 'integration-dynamics' );
                $new_links['settings'] = '<a href="' . admin_url( 'admin.php?page=wordpresscrm' ) . '">' . $settingsLabel . '</a>';
            }

            // Add a few links to the existing links array
            return array_merge( $links, $new_links );
        }, 10, 2 );

        /* Frontend includes */
        if ( !is_admin() || defined( 'DOING_AJAX' ) ) {
            new FrontendScripts();
        }
    }

    /**
     * Get the plugin url.
     *
     * @return string
     */
    public function getPluginURL() {
        $pluginURL = untrailingslashit( plugins_url( '', WORDPRESSCRM_DIR . '/integration-dynamics.php' ) );

        // strip the protocol
        return preg_replace( '~^https?://~', '//',  $pluginURL);
    }

    /**
     * Purges cache and persistent storage
     */
    public function purgeCache() {
        $this->initCache();

        $this->log->notice( 'Purging all caches and storage.' );
        $this->cache->cleanup();
        $this->metadataStorage->cleanup();
        $this->imageStorage->cleanup();
    }

}
