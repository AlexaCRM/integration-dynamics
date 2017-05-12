<?php

use AlexaCRM\WordpressCRM\Admin\Metabox\ShortcodeWizard;
use AlexaCRM\WordpressCRM\Notifier;
use AlexaCRM\WordpressCRM\Plugin;
use Symfony\Component\HttpFoundation\Request;

load_plugin_textdomain( 'integration-dynamics', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

// run migrations
require_once __DIR__ . '/update.php';

/* Shortcode Wizard init */
// view shortcode
add_action( 'wordpresscrm_sw_register', function( ShortcodeWizard $shortcodeWizard ) {
    $view = new ShortcodeWizard\Shortcode( 'view', __( 'View', 'integration-dynamics' ) );
    $view->description = __( 'Renders a Dynamics 365 view as a table.', 'integration-dynamics' );

    $entityField = new ShortcodeWizard\Field\Dropdown( 'entity', __( 'Entity name', 'integration-dynamics' ) );
    $entityField->description = __( 'Name of the entity to display a view of.', 'integration-dynamics' );
    $entityField->setValueGenerator( function() {
        try {
            $entities = ACRM()->getMetadata()->getEntitiesList();
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
            $postId = (int)$_GET['post'];
            $bindingConfig = ACRM()->getBinding()->getPostBinding( $postId );
            if ( $bindingConfig === null ) {
                return '';
            }

            return $bindingConfig['entity'];
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

/**
 * Start initializing the plugin.
 */
$pluginInstance = Plugin::instance();
$request = Request::createFromGlobals();
$pluginInstance->init(  $logger, $request );

add_action( 'admin_init', function() use ( $pluginInstance ) {
    $pluginInstance->version = get_plugin_data( WORDPRESSCRM_DIR . '/integration-dynamics.php' )['Version'];
} );

add_action( 'admin_notices', function() {
    $notifications = ACRM()->getNotifier()->getNotifications();

    foreach ( $notifications as $notification ) {
        $classes = [ 'notice', Notifier::getNoticeClass( $notification['type'] ) ];
        if ( $notification['isDismissible'] ) {
            $classes[] = 'is-dismissible';
        }
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <p><?php echo $notification['content']; ?></p>
        </div>
        <?php
    }
} );

/**
 * Returns the main plugin object.
 *
 * @return Plugin
 */
function ACRM() {
    return Plugin::instance();
}

/**
 * Returns the CRM Toolkit object.
 *
 * @return \AlexaCRM\CRMToolkit\Client|null
 */
function ASDK() {
    return ACRM()->getSdk();
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
