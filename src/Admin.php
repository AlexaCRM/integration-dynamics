<?php

namespace AlexaCRM\WordpressCRM;

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

        if ( !ACRM()->connected() ) {
            return;
        }

        new DataBinding();
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

            $pageTitle = sprintf( __( '%s &lsaquo; Dynamics 365', 'integration-dynamics' ), $tabObject->getDisplayName() );

            if ( $tabName === 'general' ) {
                add_menu_page( 'Dynamics 365 Integration Settings', 'Dynamics 365', 'manage_options',
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
                    <?php _e( 'Dynamics 365 Plugin successfully activated, <b>please configure it</b> <a href="admin.php?page=wordpresscrm">on this page</a>.', 'integration-dynamics' ); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Renders plugin settings tabs
     */
    public static function renderSettingsTabs() {
        include( WORDPRESSCRM_DIR . '/views/admin/nav_tabs.php' );
    }

}
