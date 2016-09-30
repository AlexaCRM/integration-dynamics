<?php

namespace AlexaCRM\WordpressCRM;

use AlexaCRM\CRMToolkit\Client;
use AlexaCRM\CRMToolkit\Entity\MetadataCollection;
use AlexaCRM\CRMToolkit\Settings;
use AlexaCRM\WordpressCRM\Image\AnnotationImage;
use AlexaCRM\WordpressCRM\Image\CustomImage;
use Exception;

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
     * @var string
     */
    public $plugin_name = 'Dynamics CRM Integration';

    /**
     * @var string
     */
    public $version = '';

    /**
     * @var string
     */
    public $full_plugin_name = 'Dynamics CRM Integration';

    /**
     * Shortcode prefix
     *
     * @var string
     */
    public $prefix = 'msdyncrm_';

    /**
     * Plugin home page
     *
     * @var string
     */
    public $plugin_homepage = '';

    /**
     * Plugin home page
     *
     * @var string
     */
    public $plugin_documentation_homepage = 'docs.alexacrm.com';

    /**
     * Plugin author name
     *
     * @var string
     */
    public $author_name = 'AlexaCRM';

    /**
     * Plugin support email
     *
     * @var string
     */
    public $support_email = 'support@alexacrm.com';

    /**
     * @var Plugin The single instance of the class
     */
    private static $_instance = null;

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
     * Plugin general and connection options (msdyncrm_options)
     *
     * @var array
     */
    public $options = null;

    /**
     * Main WordpressCRM Instance
     * Ensures only one instance of WordpressCRM is loaded or can be loaded.
     *
     * @static
     * @see ACRM()
     * @return Plugin Main instance
     */
    public static function instance() {
        if ( static::$_instance == null ) {
            static::$_instance = new static();
        }

        return static::$_instance;
    }

    /**
     * Cloning is forbidden.
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wordpresscrm' ), $this->version );
    }

    /**
     * Unserializing instances of this class is forbidden.
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wordpresscrm' ), $this->version );
    }

    /**
     * Plugin constructor
     *
     * @access private
     */
    private function __construct() {
        add_action( 'init', [ $this, 'session_start' ], 0 );

        // Define constants
        $this->define_constants();
    }

    /**
     * Initialize plugin
     *
     * @return void
     */
    public function init() {
        // Include required files
        $this->includes();

        // Action to call the options update, see wordpress-crm/update.php file
        do_action( 'wordpresscrm_before_load' );

        $options       = get_option( $this->prefix . 'options' );
        $this->options = $options;
        $this->initCache();

        if ( $options && isset( $options['connected'] ) && $options['connected'] == true ) {

            try {
                $this->initCrmConnection();
            } catch ( Exception $ex ) {
                $this->options['connected'] = $options['connected'] = false;
                update_option( $this->prefix . 'options', $options );
            }
        }

        if ( $this->sdk ) {
            $this->extended_includes();
        }

        if ( is_admin() ) {
            new Admin();
        }

        DataBinding::instance();

        add_action( 'admin_init', array( $this, 'admin_init' ) );

        new ShortcodeManager();

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

        if ( defined( 'WORDPRESSCRM_CACHESERVER' ) && defined( 'WORDPRESSCRM_CACHEPORT' ) ) {
            $this->options['cache'] = [
                'server' => constant( 'WORDPRESSCRM_CACHESERVER' ),
                'port'   => constant( 'WORDPRESSCRM_CACHEPORT' )
            ];
        } else {
            $this->options['cache'] = [ 'server' => 'localhost', 'port' => 11211 ];
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

        $clientSettings = new Settings( $options );
        $this->sdk      = new Client( $clientSettings, $this->cache );

        // initialize Metadata storage
        MetadataCollection::instance( $this->sdk )->setStorage( $this->metadataStorage );
    }

    /**
     * Retrieve prefixed WordPress option
     *
     * @param $field
     * @param mixed $default Default value
     *
     * @return mixed
     * @see Plugin::$prefix
     */
    public function option( $field, $default = null ) {
        return get_option( $this->prefix . $field, $default );
    }

    /**
     * Initiate a session if no session has been started
     */
    public function session_start() {
        if ( !session_id() ) {
            session_start();
        }
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
     * Define WordpressCRM plugin constants and include default config (wordpress-crm/config.php)
     */
    private function define_constants() {
        if ( !defined( 'WORDPRESSCRM_TEMPLATE_DEBUG_MODE' ) ) {
            define( 'WORDPRESSCRM_TEMPLATE_DEBUG_MODE', false );
        }

        if ( !defined( 'WORDPRESSCRM_PLUGIN_PREFIX' ) ) {
            define( 'WORDPRESSCRM_PLUGIN_PREFIX', $this->prefix );
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    private function includes() {
        /* Hooks */

        add_action( 'widgets_init', function () {
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
                $settingsLabel         = __( 'Settings', 'wordpresscrm' );
                $new_links['settings'] = '<a href="' . admin_url( 'options-general.php?page=wordpresscrm_general' ) . '">' . $settingsLabel . '</a>';
            }

            // Add a few links to the existing links array
            return array_merge( $links, $new_links );
        }, 10, 2 );

        /* Frontend includes */
        if ( !is_admin() || defined( 'DOING_AJAX' ) ) {
            $this->frontend_includes();
        }
    }

    /**
     * Include files that's require established Dynamics CRM connection
     */
    public function extended_includes() {
        new Lookup();
        new CustomImage();
        new AnnotationImage( $this->imageStorage );

        do_action( 'wordpresscrm_extended_includes' );

        include_once( WORDPRESSCRM_DIR . '/includes/template-shortcuts.php' );
    }

    /**
     * Include required frontend files.
     */
    public function frontend_includes() {
        // inject front-end scripts
        new FrontendScripts();
    }

    /**
     * Initialize values on admin init
     */
    public function admin_init() {
        $plugin_data           = get_plugin_data( WORDPRESSCRM_DIR . '/integration-dynamics.php' );
        $this->version         = $plugin_data['Version'];
        $this->plugin_homepage = $plugin_data['PluginURI'];
    }

    /**
     * Forces a JavaScript redirect when headers have been already sent.
     *
     * Execution is halted after calling this method.
     *
     * @param string $location
     */
    public function javascript_redirect( $location = null ) {
        // redirect after header here can't use wp_redirect($location);
        ?>
        <script type="text/javascript">
            <!--
            window.location = <?php echo ( $location ) ? "'" . $location . "'" : 'window.location.href'; ?>;
            //-->
        </script>
        <?php
        exit;
    }

    /**
     * Get the plugin url.
     *
     * @return string
     */
    public function plugin_url() {
        return untrailingslashit( plugins_url( '', WORDPRESSCRM_DIR . '/integration-dynamics.php' ) );
    }

    /**
     * Get the plugin dir url.
     *
     * @return string
     */
    public function plugin_dir_url() {
        return untrailingslashit( plugin_dir_url( WORDPRESSCRM_DIR . '/integration-dynamics.php' ) );
    }

    /**
     * Get the plugin path.
     *
     * @return string
     */
    public function plugin_path() {
        return WORDPRESSCRM_DIR;
    }

    /**
     * Get the plugin basename.
     *
     * @return string
     */
    public function plugin_basename() {
        return untrailingslashit( plugin_basename( __FILE__ ) );
    }

    /**
     * Get the template path.
     *
     * @return string
     */
    public function template_path() {
        return apply_filters( 'wordpress_template_path', 'wordpress-crm/' );
    }

    /**
     * Purges cache and persistent storage
     */
    public function purgeCache() {
        $this->initCache();

        $this->cache->cleanup();
        $this->metadataStorage->cleanup();
        $this->imageStorage->cleanup();
    }

    /**
     * Force 404 error to WP_Query, page or post will display page not found
     *
     * @return void
     */
    public function force404() {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
    }

}
