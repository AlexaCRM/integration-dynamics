<?php

namespace AlexaCRM\WordpressCRM\Admin;

use AlexaCRM\CRMToolkit\Entity\MetadataCollection;
use Exception;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

abstract class Tab {

    /**
     * @var string
     */
    public $pageId = '';

    /**
     * Display name of the tab
     *
     * @var string
     */
    public $displayName = '';

    public static $default_settings = [ ];

    /**
     * Settings field in WordPress (without prefix)
     *
     * @var string
     */
    protected $settingsField = '';

    protected $options = [ ];

    public function __construct() {
        $this->displayName   = $this->getDisplayName();
        $this->settingsField = ACRM()->prefix . $this->settingsField;
        $this->options       = get_option( $this->settingsField );

        $this->init();
    }

    public abstract function getDisplayName();

    protected function init() {
        register_setting( $this->settingsField, $this->settingsField, [ $this, 'sanitize_theme_options' ] );

        add_option( $this->settingsField, static::$default_settings );
    }

    public function initializeTab( $tabHookName ) {
    }

    public static function set_notice( $string ) {
        $notices = ACRM()->option( 'deferred_admin_notices' );
        if ( !is_array( $notices ) ) {
            $notices = [ ];
        }
        array_push( $notices, htmlentities( $string ) );
        update_option( ACRM()->prefix . 'deferred_admin_notices', $notices );
    }

    public static function set_errors( $string ) {
        $notices = ACRM()->option( 'deferred_admin_errors' );
        if ( !is_array( $notices ) ) {
            $notices = array();
        }
        array_push( $notices, htmlentities( $string ) );
        update_option( ACRM()->prefix . 'deferred_admin_errors', $notices );
    }

    public static function get_all_entities() {
        $entities = [ ];
        $sdk      = ASDK();

        if ( $sdk ) {
            try {
                $entitiesMetadata = MetadataCollection::instance()->getEntitiesList();

                foreach ( $entitiesMetadata as $entityLogicalName => $entityDisplayName ) {
                    $entities[ $entityLogicalName ] = array(
                        'LogicalName' => $entityLogicalName,
                        'Label'       => $entityDisplayName,
                    );
                }
                asort( $entities );
            } catch ( Exception $ex ) {
                // nop
            }
        }

        return $entities;
    }

    protected function get_field_name( $name ) {

        return sprintf( '%s[%s]', $this->settingsField, $name );
    }

    protected function get_field_id( $id ) {

        return sprintf( '%s[%s]', $this->settingsField, $id );
    }

    protected function get_field_value( $key ) {
        if ( !array_key_exists( $key, $this->options ) ) {
            return null;
        }

        return $this->options[ $key ];
    }

    public function sanitize_theme_options( $options ) {
        return $options;
    }

    public abstract function render();
}
