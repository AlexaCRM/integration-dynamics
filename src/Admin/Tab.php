<?php

namespace AlexaCRM\WordpressCRM\Admin;

use AlexaCRM\WordpressCRM\Plugin;
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
        $this->settingsField = Plugin::PREFIX . $this->settingsField;
        $this->options       = get_option( $this->settingsField );

        $this->init();
    }

    public abstract function getDisplayName();

    protected function init() {
        register_setting( $this->settingsField, $this->settingsField, [ $this, 'sanitize_theme_options' ] );

        add_option( $this->settingsField, static::$default_settings );
    }

    public function initializeTab( $tabHookName ) {}

    public static function get_all_entities() {
        $entities = [ ];
        $sdk      = ASDK();

        if ( $sdk ) {
            try {
                $entitiesMetadata = ACRM()->getMetadata()->getEntitiesList();

                foreach ( $entitiesMetadata as $entityLogicalName => $entityDisplayName ) {
                    $entities[ $entityLogicalName ] = array(
                        'LogicalName' => $entityLogicalName,
                        'Label'       => $entityDisplayName,
                    );
                }
                uasort( $entities, function( $entity1, $entity2 ) {
                    return strcmp( $entity1['Label'], $entity2['Label'] );
                } );
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
