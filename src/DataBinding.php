<?php

namespace AlexaCRM\WordpressCRM;

use AlexaCRM\CRMToolkit\KeyAttributes;
use AlexaCRM\CRMToolkit\NotAuthorizedException;
use Exception;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Implements data binding for posts and pages
 *
 * @package AlexaCRM\WordpressCRM
 */
class DataBinding {

    /**
     * @var DataBinding
     */
    protected static $_instance = null;

    /**
     * Entity record bound to the page
     *
     * @var \AlexaCRM\CRMToolkit\Entity
     */
    public $entity = null;

    /**
     * @return DataBinding
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * DataBinding constructor.
     */
    private function __construct() {
        add_action( "wp", array( $this, "checkDatabinding" ) );
    }

    /**
     * Retrieves the bound entity record or forces a 404 error if none found
     */
    public function checkDatabinding() {
        global $post;

        if ( $post ) {

            $post_entity        = maybe_unserialize( get_post_meta( $post->ID, '_wordpresscrm_databinding_entity', true ) );
            $post_parametername = maybe_unserialize( get_post_meta( $post->ID, '_wordpresscrm_databinding_parametername', true ) );
            $post_querystring   = maybe_unserialize( get_post_meta( $post->ID, '_wordpresscrm_databinding_querystring', true ) );
            $force404           = ( maybe_unserialize( get_post_meta( $post->ID, '_wordpresscrm_databinding_empty_behavior', true ) ) == "404" );

            $this->entity = apply_filters( "wordpresscrm_data_binding_entity", $this->getDataBindingEntity( $post_entity, $post_parametername, $post_querystring ), $post, $post_entity, $post_parametername, $post_querystring );

            if ( ( !$this->entity || !$this->entity->exists ) && $force404
                 && apply_filters( "wordpresscrm_data_binding_404", $force404 ) ) {
                global $wp_query;
                $wp_query->set_404();
                status_header( 404 );
            }
        }
    }

    /**
     * Retrieves a record from Dynamics CRM
     *
     * @param string $entityLogicalName
     * @param string $entityParameterName
     * @param string $requestValueParameter
     *
     * @return \AlexaCRM\CRMToolkit\Entity|null
     */
    public function getDataBindingEntity( $entityLogicalName, $entityParameterName, $requestValueParameter ) {
        if ( !$entityLogicalName || !$entityParameterName || !$requestValueParameter ) {
            return null;
        }

        if ( isset( $_GET[ $requestValueParameter ] ) && $_GET[ $requestValueParameter ] ) {
            try {
                $entityRequestValue = $_GET[ $requestValueParameter ];

                $columnSet = $this->retrieveCurrentColumnSet();

                if ( $entityParameterName == 'id' ) {
                    return ASDK()->entity( $entityLogicalName, $entityRequestValue, $columnSet );
                } else {
                    $entityMetadata = ASDK()->entity( $entityLogicalName )->metadata();

                    if ( !array_key_exists( $entityParameterName, $entityMetadata->keys ) ) {
                        return null;
                    }

                    /**
                     * @var $entityKey \AlexaCRM\CRMToolkit\Entity\EntityKey
                     */
                    $entityKey          = $entityMetadata->keys[ $entityParameterName ];
                    $entityKeyAttribute = $entityKey->getKeyAttributes();
                    if ( is_array( $entityKeyAttribute ) ) {
                        $entityKeyAttribute = $entityKeyAttribute[0]; //TODO: support for multikeys
                    }

                    $keyAttribute = new KeyAttributes();
                    $keyAttribute->add( $entityKeyAttribute, $entityRequestValue );

                    return ASDK()->entity( $entityLogicalName, $keyAttribute, $columnSet );
                }
            } catch ( NotAuthorizedException $e ) {
                Connection::setConnectionStatus( false );
            } catch ( Exception $ex ) {
                return null;
            }
        }

        return null;
    }

    /**
     * Retrieves a list of invoked fields in the current post.
     *
     * @return array
     */
    public function retrieveCurrentColumnSet() {
        global $post;

        $content = $post->post_content;
        $shortcodeRegex = get_shortcode_regex( [ ACRM()->prefix . 'field' ] );

        $count = preg_match_all( '/' . $shortcodeRegex . '/', $content, $shortcodes );

        if ( !$count ) {
            return [];
        }

        $fields = [];
        foreach ( $shortcodes[3] as $attributesString ) {
            $attributes = shortcode_parse_atts( $attributesString );

            if ( !array_key_exists( 'field', $attributes ) ) {
                continue;
            }

            $field = $attributes['field'];
            if ( strpos( $field, '.' ) !== false ) { // include related records
                $complexField = explode( '.', $field );
                $field = $complexField[0];
            }

            array_push( $fields, $field );
        }

        return $fields;
    }

    /**
     * Retrieves the default post that is bound to the given entity.
     *
     * Used by the View shortcode and Lookup Dialog to link records of the specified entity to that post.
     *
     * @param string $logicalName Logical name of the entity
     *
     * @return \WP_Post|null NULL is returned if the given entity is not linked to any post
     */
    public static function getDefaultPost( $logicalName ) {
        $transientName = static::getDefaultPostTransientName( $logicalName );

        $post = get_transient( $transientName );
        if ( $post !== false ) {
            return $post;
        }

        $args = [
            'post_type'  => [ 'page', 'post' ],
            'meta_query' => [
                [
                    'key'   => '_wordpresscrm_databinding_entity',
                    'value' => $logicalName
                ],
                [
                    'key'   => '_wordpresscrm_databinding_isdefaultview',
                    'value' => 'true'
                ]
            ]
        ];
        $posts = get_posts( $args );

        if ( !count( $posts ) ) {
            return null;
        }

        set_transient( $transientName, $posts[0], 2 * 60 * 60 * 24 );

        return $posts[0];
    }

    /**
     * Returns the transient key for a given entity name.
     *
     * @see DataBinding::getDefaultPost()
     *
     * @param $logicalName
     *
     * @return string
     */
    public static function getDefaultPostTransientName( $logicalName ) {
        return 'wpcrm_databinding_' . $logicalName;
    }

}
