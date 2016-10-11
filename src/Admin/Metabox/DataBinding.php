<?php

namespace AlexaCRM\WordpressCRM\Admin\Metabox;

use AlexaCRM\WordpressCRM\Admin\Tab;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class DataBinding {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'registerMetaboxes' ] );
        add_action( 'save_post', [ $this, 'save_postdata' ] );

        add_action( 'wp_ajax_retrieve_entity_keys', [ $this, 'retrieve_entity_keys' ] );
        add_action( 'wp_ajax_nopriv_retrieve_entity_keys', [ $this, 'retrieve_entity_keys' ] );
    }

    public function retrieve_entity_keys() {
        if ( !isset( $_POST['entityLogicalName'] ) ) {
            wp_send_json_error();
        }

        $entityLogicalName = $_POST['entityLogicalName'];
        $entity            = ASDK()->entity( $entityLogicalName );

        /**
         * @var $entityKeys \AlexaCRM\CRMToolkit\Entity\EntityKey[]
         */
        $entityKeys = $entity->metadata()->keys;
        wp_send_json_success( $entityKeys );
    }

    /**
     * Add our custom meta box to all support post types
     */
    public function registerMetaboxes() {
        $enabled_post_types = get_option( 'wordpresscrm_custom_post_types', array() );
        $supported_pages    = apply_filters( 'wp_access_supported_pages', array_merge( array(
            'page',
            'post'
        ), $enabled_post_types ) );

        foreach ( $supported_pages as $page_type ) {
            add_meta_box(
                'wordpresscrm_databinding_meta',
                __( 'Dynamics CRM Data Binding', 'integration-dynamics' ),
                [ $this, 'render' ],
                $page_type, 'side', 'high' );
        }
    }

    /**
     * Display the various per-page/per-post options in the sidebar
     */
    public function render() {
        global $post;

        if ( ACRM()->connected() ) {

            $entities = Tab::get_all_entities();

            $post_entity         = maybe_unserialize( get_post_meta( $post->ID, '_wordpresscrm_databinding_entity', true ) );
            $post_parametername  = maybe_unserialize( get_post_meta( $post->ID, '_wordpresscrm_databinding_parametername', true ) );
            $post_isdefaultview  = maybe_unserialize( get_post_meta( $post->ID, '_wordpresscrm_databinding_isdefaultview', true ) );
            $post_querystring    = maybe_unserialize( get_post_meta( $post->ID, '_wordpresscrm_databinding_querystring', true ) );
            $post_empty_behavior = maybe_unserialize( get_post_meta( $post->ID, '_wordpresscrm_databinding_empty_behavior', true ) );

            if ( $post_isdefaultview == 'true' ) {
                $post_isdefaultview = 'checked="checked"';
            }

            include( ACRM()->plugin_path() . '/views/admin/meta_box.php' );
        }
    }

    /**
     * Saves our custom meta data for the post being saved
     */
    public function save_postdata( $post_id ) {
        // Security check
        if ( !isset( $_POST['wordpresscrm_databinding_nonce'] ) ) {
            return $post_id;
        }

        if ( !wp_verify_nonce( $_POST['wordpresscrm_databinding_nonce'], 'wordpresscrm_databinding' ) ) {
            return $post_id;
        }

        // Meta data isn't transmitted during autosaves so don't do anything
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        if ( !isset( $_POST['wordpresscrm_databinding'] ) ) {
            return $post_id;
        }

        $entityName = null;

        if ( isset( $_POST['wordpresscrm_databinding']['entity'] ) && $_POST['wordpresscrm_databinding']['entity'] ) {
            /* TODO: Serialization for multiple values, for multiple data-binding */
            //update_post_meta($post_id, '_wordpresscrm_databinding_entity', serialize($_POST['wordpresscrm_databinding']['entity']));

            $entityName = $_POST['wordpresscrm_databinding']['entity'];

            update_post_meta( $post_id, '_wordpresscrm_databinding_entity', $_POST['wordpresscrm_databinding']['entity'] );
        } else {
            delete_post_meta( $post_id, '_wordpresscrm_databinding_entity' );
        }

        if ( isset( $_POST['wordpresscrm_databinding']['parametername'] ) && $_POST['wordpresscrm_databinding']['parametername'] ) {
            /* TODO: Serialization for multiple values, for multiple data-binding */
            //update_post_meta($post_id, '_wordpresscrm_databinding_parametername', serialize($_POST['wordpresscrm_databinding']['parametername']));

            update_post_meta( $post_id, '_wordpresscrm_databinding_parametername', $_POST['wordpresscrm_databinding']['parametername'] );
        } else {
            delete_post_meta( $post_id, '_wordpresscrm_databinding_parametername' );
        }

        if ( isset( $_POST['wordpresscrm_databinding']['isdefaultview'] ) && $_POST['wordpresscrm_databinding']['isdefaultview'] == 'true' ) {
            $this->clearOtherDefaultViews( $entityName );

            update_post_meta( $post_id, '_wordpresscrm_databinding_isdefaultview', 'true' );
        } else {
            delete_post_meta( $post_id, '_wordpresscrm_databinding_isdefaultview' );
        }

        if ( isset( $_POST['wordpresscrm_databinding']['querystring'] ) && strlen( $_POST['wordpresscrm_databinding']['querystring'] ) > 0 ) {
            update_post_meta( $post_id, '_wordpresscrm_databinding_querystring', $_POST['wordpresscrm_databinding']['querystring'] );
        } else {
            update_post_meta( $post_id, '_wordpresscrm_databinding_querystring', 'id' );
        }

        if ( isset( $_POST['wordpresscrm_databinding']['empty_behavior'] ) && strlen( $_POST['wordpresscrm_databinding']['empty_behavior'] ) > 0 ) {
            update_post_meta( $post_id, '_wordpresscrm_databinding_empty_behavior', $_POST['wordpresscrm_databinding']['empty_behavior'] );
        } else {
            update_post_meta( $post_id, '_wordpresscrm_databinding_empty_behavior', '' );
        }

        return $post_id;
    }

    public function clearOtherDefaultViews( $entity = null ) {
        if ( $entity == null ) {
            return;
        }

        $args  = array(
            'post_type'  => array( 'page', 'post' ),
            'meta_query' => array(
                array(
                    'key'   => '_wordpresscrm_databinding_entity',
                    'value' => $entity
                ),
                array(
                    'key'   => '_wordpresscrm_databinding_isdefaultview',
                    'value' => 'true'
                )

            )
        );
        $posts = get_posts( $args );

        foreach ( $posts as $post ) {
            delete_post_meta( $post->ID, '_wordpresscrm_databinding_isdefaultview' );
        }

        $transientName = \AlexaCRM\WordpressCRM\DataBinding::getDefaultPostTransientName( $entity );
        delete_transient( $transientName );
    }

}
