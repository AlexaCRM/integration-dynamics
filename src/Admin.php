<?php

namespace AlexaCRM\WordpressCRM;

use AlexaCRM\CRMToolkit\Entity\MetadataCollection;
use AlexaCRM\WordpressCRM\Admin\Metabox\DataBinding;
use AlexaCRM\WordpressCRM\Admin\Metabox\ShortcodeWizard;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Manages Admin UI for the plugin
 *
 * @package AlexaCRM\WordpressCRM
 */
class Admin {

    /**
     * List of tabs (tabSlug => (FQCN, priority))
     *
     * Extended via `wordpresscrm_tabs` WordPress filter
     *
     * @var array
     */
    public static $tabs = [
        'general' => [ '\AlexaCRM\WordpressCRM\Admin\Tab\General', 10 ],
        'forms' => [ '\AlexaCRM\WordpressCRM\Admin\Tab\Forms', 20 ],
        // 'views' => [ '\AlexaCRM\WordpressCRM\Admin\Tab\Views', 30 ],
        'attachments' => [ '\AlexaCRM\WordpressCRM\Admin\Tab\Attachments', 40 ],
        'messages' => [ '\AlexaCRM\WordpressCRM\Admin\Tab\Messages', 50 ],
        'about' => [ '\AlexaCRM\WordpressCRM\Admin\Tab\About', 60 ],
    ];

    /**
     * Admin menu item icon
     *
     * @var string
     */
    private static $adminLogo = 'data:image/svg+xml;base64,PHN2ZyBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSI0NTYgMjg2LjIgMTY0IDE2NCI+PHN0eWxlPnBhdGh7ZmlsbDojZWVlO308L3N0eWxlPjxwYXRoIGQ9Ik01NzQuNyAzMjMuNWMtOC4zIDIxLTIxLjQgNDAuMy0zNC41IDU0VjQxMGwtMS41LjVoLTJjLTQ1IDMtNzEuOCAyNS43LTcxLjggMjUuNyA1MC40LTMxIDEwOC4yLTE3IDEwOC4yLTE2LjVsNC40LTF2LTk0LjJsLTMtMXoiLz48cGF0aCBkPSJNNjA4LjYgMzQ2LjRzLTUgNi4zLTE5LjQgMTkuNGMtMy40IDMtNS4zIDUtOC43IDcuOHY0OC42bC0zIC41cy0xMi41LTQtMzQuNC01Yy0yMS4zLS40LTUzLjMgNS40LTc3LjYgMTguNmgxczY1LjUtMjUuNyAxNDEuOC41bDMtMXYtODguNGwtMi42LTF6Ii8+PHBhdGggZD0iTTUzNy4zIDMwMC43bC0zLjQtMXYuNWMtLjYgMy00IDI2LjItMjEgNTguMy05LjcgMTktMjQuOCA0My4yLTQ4IDY3djExLjJzMjQuMi0yMy4zIDY4LjQtMjcuN2gxLjVsMi4zLTFWMzAwLjd6Ii8+PC9zdmc+';

    /**
     * @var \AlexaCRM\WordpressCRM\Admin\Tab[]
     */
    public static $tabsCollection = [ ];

    /**
     * Admin constructor.
     */
    function __construct() {
        add_action( 'admin_menu', [ $this, 'init' ] );

        add_action( 'admin_notices', [ $this, 'admin_notices_plugin_activated' ] );
        add_action( 'admin_notices', [ $this, 'admin_notices' ] );
        add_action( 'admin_notices', [ $this, 'admin_errors' ] );

        new DataBinding();

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

        new ShortcodeWizard();
    }

    /**
     * Initializes Admin UI
     */
    public function init() {
        $adminTabs = apply_filters( 'wordpresscrm_tabs', static::$tabs );
        uasort( $adminTabs, function( $first, $second ) {
            if ( $first[1] === $second[1] ) {
                return 0;
            }

            return ( $first[1] < $second[1] )? -1 : 1;
        } );
        foreach ( $adminTabs as $tabName => $tabSettings ) {
            $tabClassName = $tabSettings[0];

            /**
             * @var \AlexaCRM\WordpressCRM\Admin\Tab $tabObject
             */
            static::$tabsCollection[ $tabName ] = $tabObject = new $tabClassName();

            $pageTitle = sprintf( __( '%s &lsaquo; Dynamics CRM', 'integration-dynamics' ), $tabObject->getDisplayName() );

            if ( $tabName === 'general' ) {
                add_menu_page( 'Dynamics CRM Integration Settings', 'Dynamics CRM', 'manage_options',
                    'wordpresscrm', [ $tabObject, 'render' ], static::$adminLogo );
                $tabHookName = add_submenu_page( 'wordpresscrm', $pageTitle, $tabObject->getDisplayName(), 'manage_options',
                    'wordpresscrm', [ $tabObject, 'render' ] );
            } else {
                $tabHookName = add_submenu_page( 'wordpresscrm', $pageTitle, $tabObject->getDisplayName(),
                    'manage_options', 'wordpresscrm_' . $tabObject->pageId, [ $tabObject, 'render' ] );
            }

            $tabObject->initializeTab( $tabHookName );
        }
    }

    /**
     * Renders a notice in the Plugins screen if the plugin is not connected
     */
    function admin_notices_plugin_activated() {
        $screen = get_current_screen();

        if ( !ACRM()->connected() && $screen->base == "plugins" ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php _e( 'Dynamics CRM Plugin successfully activated, <b>please configure it</b> <a href="admin.php?page=wordpresscrm">on this page</a>.', 'integration-dynamics' ); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Renders issued notifications (success messages, etc.)
     */
    public function admin_notices() {
        if ( $notices = ACRM()->option( 'deferred_admin_notices' ) ) {
            if ( is_array( $notices ) ) {
                foreach ( $notices as $notice ) {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p>
                            <?php _e( $notice, 'integration-dynamics' ); ?>
                        </p>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php _e( $notices, 'integration-dynamics' ); ?>
                    </p>
                </div>
                <?php
            }
            delete_option( ACRM()->prefix . 'deferred_admin_notices' );
        }
    }

    /**
     * Renders issued error messages
     */
    public function admin_errors() {
        if ( $notices = ACRM()->option( 'deferred_admin_errors' ) ) {
            if ( is_array( $notices ) ) {
                foreach ( $notices as $notice ) {
                    ?>
                    <div class="error notice-error is-dismissible">
                        <p>
                            <?php _e( $notice, 'integration-dynamics' ); ?>
                        </p>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="error notice-error is-dismissible">
                    <p>
                        <?php _e( $notices, 'integration-dynamics' ); ?>
                    </p>
                </div>
                <?php
            }
            delete_option( ACRM()->prefix . 'deferred_admin_errors' );
        }
    }

    /**
     * Renders plugin settings tabs
     */
    public static function renderSettingsTabs() {
        include( WORDPRESSCRM_DIR . '/views/admin/nav_tabs.php' );
    }

}
