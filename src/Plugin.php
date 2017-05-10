<?php

namespace AlexaCRM\WordpressCRM;

use AlexaCRM\CRMToolkit\Client;
use AlexaCRM\CRMToolkit\Entity\MetadataCollection;
use AlexaCRM\CRMToolkit\Settings;
use AlexaCRM\CRMToolkit\StorageInterface;
use AlexaCRM\WordpressCRM\Image\AnnotationImage;
use AlexaCRM\WordpressCRM\Image\CustomImage;
use Exception;
use Monolog\Logger;
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
     * @deprecated 1.1.32 In favor of WORDPRESSCRM_VERSION
     */
    public $version = '';

    /**
     * Current request.
     *
     * @var Request
     */
    public $request = null;

    /**
     * Plugin general and connection options (msdyncrm_options)
     *
     * @var array
     */
    public $options = null;

    /**
     * @var Plugin
     */
    private static $instance;

    /**
     * Different facilities of the plugin (cache, storage, SDK, etc.)
     *
     * @var array
     */
    private $facilities;

    /**
     * Collection of storage.
     *
     * @var PersistentStorage[]
     */
    private $storage;

    /**
     * Cloning is forbidden.
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'integration-dynamics' ), WORDPRESSCRM_VERSION );
    }

    /**
     * Unserializing instances of this class is forbidden.
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'integration-dynamics' ), WORDPRESSCRM_VERSION );
    }

    /**
     * Plugin constructor.
     */
    private function __construct() {
        $facilities = [ 'sdk', 'binding', 'template', 'cache', 'logger', 'notifier' ];
        foreach ( $facilities as $facility ) {
            $this->facilities[$facility] = null;
        }

        /**
         * Allows to modify the list of pre-defined storage.
         * Such modification for custom storage is required in order
         * to purge it in case it hasn't been initialized during the request yet.
         *
         * @param string[]  List of storage names
         */
        $storage = apply_filters( 'wordpresscrm_storage_list', [ 'metadata', 'images' ] );
        foreach ( $storage as $storageName ) {
            $this->storage[$storageName] = null;
        }
    }

    /**
     * Access method for the plugin.
     *
     * @return Plugin
     */
    public static function instance() {
        if ( is_null( static::$instance ) ) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Initialize plugin
     *
     * @param Logger $logger
     * @param Request $request Request data, i.e. from Request::createFromGlobals()
     */
    public function init( Logger $logger, Request $request ) {
        $this->facilities['logger'] = $logger;
        $logger->debug( 'Initializing Dynamics 365 Integration.' );

        $this->request = $request;

        // Include required files
        $this->includes();

        // Action to call the options update, see wordpress-crm/update.php file
        do_action( 'wordpresscrm_before_load' );

        $options       = get_option( static::PREFIX . 'options' );
        $this->options = $options;

        // Initialize CRM metadata and client early
        $this->getSdk();

        if ( $this->connected() ) {
            new LookupDialog();
            new CustomImage();
            new AnnotationImage( $this->getStorage( 'images' ) );

            do_action( 'wordpresscrm_extended_includes' );

            include_once( WORDPRESSCRM_DIR . '/includes/template-shortcuts.php' );
        }

        if ( is_admin() ) {
            $logger->debug( 'Initializing admin UI.' );
            new Admin();
        }

        if ( !is_admin() ) {
            add_action( 'after_setup_theme', function() use ( $logger ) {
                $logger->debug( 'Initializing shortcodes.' );

                new ShortcodeManager();
            } );
        }

        // Loaded action
        do_action( 'wordpresscrm_loaded' );
    }

    /**
     * Initializes connection to the CRM
     */
    private function initCrmConnection() {
        $options = $this->options;

        $this->getLogger()->debug( 'Initializing PHP CRM Toolkit.' );

        $clientSettings = new Settings( $options );
        $this->facilities['sdk'] = new Client( $clientSettings, $this->getCache(), $this->getLogger()->withName( 'crmtoolkit' ) );

        $this->getLogger()->debug( 'Finished initializing PHP CRM Toolkit.' );

        // initialize Metadata storage
        $this->getLogger()->debug( 'Initializing PHP CRM Toolkit Metadata storage.' );
        MetadataCollection::instance( $this->facilities['sdk'] )->setStorage( $this->getStorage( 'metadata' ) );

        return $this->facilities['sdk'];
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
        /**
         * Add 'Settings' link to the list of plugins
         */
        add_filter( 'plugin_action_links', function ( $links, $file ) {
            // Return normal links if not wordpresscrm
            if ( 'integration-dynamics/integration-dynamics.php' !== $file ) {
                return $links;
            }

            // New links to merge into existing links
            $new_links = [];

            // Settings page link
            if ( current_user_can( 'manage_options' ) ) {
                $settingsLabel         = __( 'Settings', 'integration-dynamics' );
                $new_links['settings'] = '<a href="' . admin_url( 'admin.php?page=wordpresscrm' ) . '">' . $settingsLabel . '</a>';
            }

            // Add a few links to the existing links array
            return array_merge( $new_links, $links  );
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
        $this->getLogger()->notice( 'Purging all caches and storage.' );

        // Purge cache
        $this->getCache()->cleanup();

        // Purge each storage
        foreach ( array_keys( $this->storage ) as $storageName ) {
            $this->getStorage( $storageName )->cleanup();
        }
    }

    /**
     * Returns a PHP CRM Toolkit Client instance.
     *
     * @return Client|null  Null returned if failed to initialize
     */
    public function getSdk() {
        if ( $this->facilities['sdk'] instanceof Client ) {
            return $this->facilities['sdk'];
        }

        if ( $this->options && isset( $this->options['connected'] ) && $this->options['connected'] == true ) {
            try {
                return $this->initCrmConnection();
            } catch ( Exception $ex ) {
                $this->getLogger()->critical( 'Caught exception while initializing connection to CRM.', [ 'exception' => $ex ] );
                $this->options['connected'] = $options['connected'] = false;
                update_option( static::PREFIX . 'options', $options );
            }
        }

        return null;
    }

    /**
     * Returns a Logger implementation.
     *
     * @return Logger
     */
    public function getLogger() {
        return $this->facilities['logger'];
    }

    /**
     * @param string $storageName
     *
     * @return PersistentStorage
     */
    public function getStorage( $storageName ) {
        if ( array_key_exists( $storageName, $this->storage ) && $this->storage[$storageName] instanceof StorageInterface ) {
            return $this->storage[$storageName];
        }

        $this->getLogger()->debug( 'Initializing storage <' . $storageName . '>.' );
        $this->storage[$storageName] = new PersistentStorage( $storageName );

        return $this->storage[$storageName];
    }

    /**
     * @return Cache
     */
    public function getCache() {
        if ( $this->facilities['cache'] instanceof Cache ) {
            return $this->facilities['cache'];
        }

        $this->getLogger()->debug( 'Initializing cache.' );

        $this->options['cache'] = [ 'server' => 'localhost', 'port' => 11211 ];
        if ( defined( 'WORDPRESSCRM_CACHESERVER' ) && defined( 'WORDPRESSCRM_CACHEPORT' ) ) {
            $this->options['cache'] = [
                'server' => WORDPRESSCRM_CACHESERVER,
                'port'   => WORDPRESSCRM_CACHEPORT,
            ];
        }

        $this->facilities['cache'] = new Cache( $this->options['cache'] );

        return $this->facilities['cache'];
    }

    /**
     * @return Template
     */
    public function getTemplate() {
        if ( !( $this->facilities['template'] instanceof Template ) ) {
            $this->facilities['template'] = new Template();
        }

        return $this->facilities['template'];
    }

    /**
     * @return Binding
     */
    public function getBinding() {
        if ( !( $this->facilities['binding'] instanceof Binding ) ) {
            $this->facilities['binding'] = new Binding();
        }

        return $this->facilities['binding'];
    }

    /**
     * @return MetadataCollection
     */
    public function getMetadata() {
        $metadata = MetadataCollection::instance( $this->getSdk() );
        $metadata->setStorage( $this->getStorage( 'metadata' ) );

        return $metadata;
    }

    /**
     * @return Notifier
     */
    public function getNotifier() {
        if ( !( $this->facilities['notifier'] instanceof Notifier ) ) {
            $this->facilities['notifier'] = new Notifier();
        }

        return $this->facilities['notifier'];
    }

}
