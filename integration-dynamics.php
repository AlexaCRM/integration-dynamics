<?php
/*
 * Plugin Name: Dynamics CRM Integration
 * Plugin URI: https://wordpress.org/plugins/integration-dynamics/
 * Description: The easiest way to connect Dynamics CRM with WordPress.
 * Version: 1.1.23
 * Author: AlexaCRM
 * Author URI: http://alexacrm.com
 * Text Domain: integration-dynamics
 * Domain Path: /languages
 */

use AlexaCRM\CRMToolkit\Entity\MetadataCollection;
use AlexaCRM\WordpressCRM\Admin\Metabox\ShortcodeWizard;
use AlexaCRM\WordpressCRM\Log;
use AlexaCRM\WordpressCRM\Plugin;
use Symfony\Component\HttpFoundation\Request;

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

require_once __DIR__ . '/vendor/autoload.php'; // Composer autoloader

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

/* Shortcode Wizard init */
// view shortcode
add_action( 'wordpresscrm_sw_register', function( ShortcodeWizard $shortcodeWizard ) {
    $view = new ShortcodeWizard\Shortcode( 'view', __( 'View', 'integration-dynamics' ) );
    $view->description = __( 'Renders a Dynamics CRM View as a table.', 'integration-dynamics' );

    $entityField = new ShortcodeWizard\Field\Dropdown( 'entity', __( 'Entity name', 'integration-dynamics' ) );
    $entityField->description = __( 'Name of the entity to display a view of.', 'integration-dynamics' );
    $entityField->setValueGenerator( function() {
        try {
            $entities = MetadataCollection::instance()->getEntitiesList();
            asort( $entities );

            return $entities;
        } catch ( \Exception $e ) {
            throw $e;
        }
    } );
    $view->registerField( $entityField );

    $viewField = new ShortcodeWizard\Field\Dropdown( 'view', __( 'Entity View name', 'integration-dynamics' ) );
    $viewField->description = __( 'Name of the view to display.', 'integration-dynamics' );
    $viewField->bindingFields = [ 'entity' ];
    $viewField->setValueGenerator( function( $values ) {
        $views = [];

        if ( !array_key_exists( 'entity', $values ) ) {
            throw new \InvalidArgumentException( __( 'Entity name is not specified', 'integration-dynamics' ) );
        }

        $entityName = trim( $values['entity'] );

        if ( $entityName === '' ) {
            throw new \InvalidArgumentException( __( 'Empty entity name in the request', 'integration-dynamics' ) );
        }

        $entity = ASDK()->entity( $entityName );

        $fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
						<entity name="userquery">
							<attribute name="name" />
							<attribute name="returnedtypecode" />
							 <filter type="and">
								<condition attribute="returnedtypecode" operator="eq" value="' . $entity->metadata()->objectTypeCode . '" />
							  </filter>
						</entity>
					  </fetch>';

        $userQueries = ASDK()->retrieveMultiple( $fetch );

        $fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
						<entity name="savedquery">
							<attribute name="name" />
							<attribute name="returnedtypecode" />
							 <filter type="and">
								<condition attribute="returnedtypecode" operator="eq" value="' . $entity->metadata()->objectTypeCode . '" />
							  </filter>
						</entity>
					  </fetch>';

        $savedQueries = ASDK()->retrieveMultiple( $fetch );

        $viewEntities = array_merge( $userQueries->Entities, $savedQueries->Entities );

        foreach ( $viewEntities as $viewEntity ) {
            $views[$viewEntity->displayname] = $viewEntity->displayname;
        }

        asort( $views );

        return $views;
    } );
    $view->registerField( $viewField );

    $countField = new ShortcodeWizard\Field\Number( 'count', __( 'Records per page', 'integration-dynamics' ) );
    $countField->description = __( '0 disables pagination for the view.', 'integration-dynamics' );
    $countField->setStaticValueGenerator( function() {
        return 10;
    } );
    $view->registerField( $countField );

    $shortcodeWizard->registerShortcode( $view );
} );

// field shortcode
add_action( 'wordpresscrm_sw_register', function( ShortcodeWizard $shortcodeWizard ) {
    $field = new ShortcodeWizard\Shortcode( 'field', __( 'Field', 'integration-dynamics' ) );
    $field->description = __( 'Renders a field value for the current CRM record on a data-bound page.', 'integration-dynamics' );

    $entityField = new ShortcodeWizard\Field\Hidden( 'entity' );
    if ( array_key_exists( 'post', $_GET ) ) {
        $entityField->setStaticValueGenerator( function() {
            return trim( maybe_unserialize( get_post_meta( $_GET['post'], '_wordpresscrm_databinding_entity', true ) ) );
        } );
    }
    $field->registerField( $entityField );

    $attributeField = new ShortcodeWizard\Field\Dropdown( 'field', __( 'Attribute name', 'integration-dynamics' ) );
    $attributeField->description = __( 'Entity attribute to render.', 'integration-dynamics' );
    $attributeField->bindingFields = [ 'entity' ];
    $attributeField->setValueGenerator( function( $values ) {
        $attributes = [];

        $entity = ASDK()->entity( $values['entity'] );

        foreach ( $entity->attributes as $attribute ) {
            $attributes[$attribute->logicalName] = $attribute->logicalName . ' (' . $attribute->label . ')';
        }

        asort( $attributes );

        return $attributes;
    } );

    $field->registerField( $attributeField );
    $shortcodeWizard->registerShortcode( $field );
} );

$pluginInstance = new Plugin();
$request = Request::createFromGlobals();
$pluginInstance->init(  $logger, $request );

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
