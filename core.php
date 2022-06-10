<?php

use AlexaCRM\WordpressCRM\Admin\Metabox\ShortcodeWizard;
use AlexaCRM\WordpressCRM\Notifier;
use AlexaCRM\WordpressCRM\Plugin;
use Symfony\Component\HttpFoundation\Request;

load_plugin_textdomain( 'integration-dynamics', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

/* Shortcode Wizard init */
// view shortcode
add_action( 'wordpresscrm_sw_register', function( ShortcodeWizard $shortcodeWizard ) {
    $view = new ShortcodeWizard\Shortcode( 'view', __( 'View', 'integration-dynamics' ) );
    $view->description = __( 'Renders a Dynamics 365 view as a table.', 'integration-dynamics' );

    $entityField = new ShortcodeWizard\Field\Dropdown( 'entity', __( 'Table', 'integration-dynamics' ) );
    $entityField->description = __( 'Name of the table to display a view of.', 'integration-dynamics' );
    $entityField->setValueGenerator( function() {
        try {
            $entities = ACRM()->getMetadata()->getEntitiesList();
            asort( $entities );
	        array_walk( $entities, function ( &$val, $logical ) {
		        $val = "$val ($logical)";
	        } );
	        return $entities;
        } catch ( \Exception $e ) {
            throw $e;
        }
    } );
    $view->registerField( $entityField );

    $viewField = new ShortcodeWizard\Field\Dropdown( 'name', __( 'Table View name', 'integration-dynamics' ) );
    $viewField->description = __( 'Name of the view to display.', 'integration-dynamics' );
    $viewField->bindingFields = [ 'entity' ];
    $viewField->setValueGenerator( function( $values ) {
        $client = ACRM()->getSdk();
        if ( !$client ) {
            throw new \Exception( __( 'Not connected to CRM', 'integration-dynamics' ) );
        }

        $views = [];

        if ( !array_key_exists( 'entity', $values ) ) {
            throw new \InvalidArgumentException( __( 'Table is not specified', 'integration-dynamics' ) );
        }

        $entityName = trim( $values['entity'] );

        if ( $entityName === '' ) {
            throw new \InvalidArgumentException( __( 'Empty table name in the request', 'integration-dynamics' ) );
        }

        $entity = $client->entity( $entityName );

        $fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
						<entity name="userquery">
							<attribute name="name" />
							<attribute name="returnedtypecode" />
							 <filter type="and">
								<condition attribute="returnedtypecode" operator="eq" value="' . $entity->metadata()->objectTypeCode . '" />
							  </filter>
						</entity>
					  </fetch>';

        $userQueries = $client->retrieveMultiple( $fetch );

        $fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
						<entity name="savedquery">
							<attribute name="name" />
							<attribute name="returnedtypecode" />
							 <filter type="and">
								<condition attribute="returnedtypecode" operator="eq" value="' . $entity->metadata()->objectTypeCode . '" />
							  </filter>
						</entity>
					  </fetch>';

        $savedQueries = $client->retrieveMultiple( $fetch );

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

// form shortcode
add_action( 'wordpresscrm_sw_register', function ( ShortcodeWizard $shortcodeWizard ) {
	$form              = new ShortcodeWizard\Shortcode( 'form', __( 'Form', 'integration-dynamics' ) );
	$form->description = __( 'Renders a Dynamics 365 form.', 'integration-dynamics' );

	$entityField              = new ShortcodeWizard\Field\Dropdown( 'entity', __( 'Table', 'integration-dynamics' ) );
	$entityField->description = __( 'Name of the table to display a form of.', 'integration-dynamics' );
	$entityField->setValueGenerator( function () {
		try {
			$entities = ACRM()->getMetadata()->getEntitiesList();
			asort( $entities );
			array_walk( $entities, function ( &$val, $logical ) {
				$val = "$val ($logical)";
			} );

			return $entities;
		} catch ( \Exception $e ) {
			throw $e;
		}
	} );
	$form->registerField( $entityField );

	$formField                = new ShortcodeWizard\Field\Dropdown( 'name', __( 'Form', 'integration-dynamics' ) );
	$formField->description   = __( 'Name of the form of the table.', 'integration-dynamics' );
	$formField->bindingFields = [ 'entity' ];
	$formField->setValueGenerator( function ( $values ) {
		$client = ACRM()->getSdk();
		if ( ! $client ) {
			throw new \Exception( __( 'Not connected to CRM', 'integration-dynamics' ) );
		}

		$forms = [];

		if ( ! array_key_exists( 'entity', $values ) ) {
			throw new \InvalidArgumentException( __( 'Table is not specified', 'integration-dynamics' ) );
		}

		$entityName = trim( $values['entity'] );

		if ( $entityName === '' ) {
			throw new \InvalidArgumentException( __( 'Empty table name in the request', 'integration-dynamics' ) );
		}

		$entity = $client->entity( $entityName );

		$fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
						<entity name="systemform">
                            <attribute name="name" />
                            <attribute name="objecttypecode" />
							 <filter type="and">
								<condition attribute="objecttypecode" operator="eq" value="' . $entity->metadata()->objectTypeCode . '" />
							  </filter>
						</entity>
					  </fetch>';

		$systemForms = $client->retrieveMultiple( $fetch );
		foreach ( $systemForms->Entities as $form ) {
			$forms[ $form->name ] = $form->name;
		}


		return $forms;
	} );
	$form->registerField( $formField );
	$shortcodeWizard->registerShortcode( $form );
} );

add_action( 'wp_ajax_wpcrm_log_verbosity', function() {
    $request = ACRM()->request->request;

    update_option( 'wpcrm_log_level', $request->get( 'logVerbosity', WORDPRESSCRM_EFFECTIVE_LOG_LEVEL ) );

    wp_send_json_success();
} );

add_action( 'wp_ajax_wpcrm_log', function() {
    if ( class_exists( '\ZipArchive' ) && ( $zipPath = tempnam( sys_get_temp_dir(), 'wpcrm' ) ) ) {
        $zip = new ZipArchive();
        $zip->open( $zipPath, ZipArchive::OVERWRITE );
        $zip->addGlob( WORDPRESSCRM_STORAGE . '/*.log', 0, [ 'add_path' => DIRECTORY_SEPARATOR, 'remove_all_path' => true ] );
        $zip->close();

        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/octet-stream' );

        $date = date( 'YmdHi' );
        header( "Content-Disposition: attachment; filename=integration-dynamics_logs_{$date}.zip" );

        readfile( $zipPath );

        unlink( $zipPath );

        wp_die();
    }

    $logFiles = glob( WORDPRESSCRM_STORAGE . '/*.log' );
    rsort( $logFiles );
    $logPath = array_shift( $logFiles );

    header( 'Content-Description: File Transfer' );
    header( 'Content-Type: application/octet-stream' );

    $filename = basename( $logPath );
    header( 'Content-Disposition: attachment; filename=' . $filename );

    readfile( $logPath );

    wp_die();
} );

/**
 * Don't texturize Twig shortcode contents.
 */
add_filter( 'no_texturize_shortcodes', function( $shortcodes ) {
    $shortcodes[] = Plugin::PREFIX . 'twig';

    return $shortcodes;
} );

add_filter( 'pre_handle_404', function( $preempt, \WP_Query $query ) {
    if ( is_admin() || !( $query->is_singular() && ACRM()->connected() ) ) {
        return $preempt;
    }

    $post = $query->post;
    if ( !$post ) { // non-existing post
        return $preempt;
    }

    $binding = ACRM()->getBinding();
    $bindingConfig = $binding->getPostBinding( $post->ID );
    if ( $bindingConfig === null ) {
        return $preempt;
    }

    $shouldTrigger404 = ( $bindingConfig['empty'] === '404' );
    if ( apply_filters( 'wordpresscrm_data_binding_404', $shouldTrigger404 ) && $binding->getEntity( $post->ID ) === null ) {
        $query->set_404();
    }

    return $preempt;
}, 10, 2 );

add_filter( 'option_msdyncrm_options', function( $value, $option ) {
    $pwdHandler = new \AlexaCRM\WordpressCRM\PasswordHandler();
    if ( !empty( $value['password'] ) ) {
        $value['password'] = $pwdHandler->decrypt( $value['password'] );
    }
    if ( !empty( $value['clientSecret'] ) ) {
        $value['clientSecret'] = $pwdHandler->decrypt( $value['clientSecret'] );
    }

    return $value;
}, 10, 2 );

add_filter( "pre_update_option_msdyncrm_options", function( $value, $old_value, $option ) {
    $pwdHandler = new \AlexaCRM\WordpressCRM\PasswordHandler();
    if ( !empty( $value['password'] ) ) {
        $value['password'] = $pwdHandler->encrypt( $value['password'] );
    }
    if ( !empty( $value['clientSecret'] ) ) {
        $value['clientSecret'] = $pwdHandler->encrypt( $value['clientSecret'] );
    }

    return $value;
}, 10, 3 );

/**
 * Start initializing the plugin.
 */
$pluginInstance = Plugin::instance();
$request = Request::createFromGlobals();
$pluginInstance->init(  $logger, $request );

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

    // Stop buffering the output, flush what's left.
    wp_ob_end_flush_all();

    /*
     * Redirect using front-end JavaScript if the headers have already been sent.
     */
    ?>
    <script type="text/javascript">
        <!--
        ( function() {
            var newLocation = '.';
            newLocation = <?php echo ( $location ) ? json_encode( $location ) : 'window.location.href'; ?>;

            if ( newLocation === '.' ) {
                window.location = window.location.href;
                return;
            }

            window.location = newLocation;
        }() );
        //-->
    </script>
    <?php
    exit();
}
