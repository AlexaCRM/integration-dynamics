<?php

namespace AlexaCRM\WordpressCRM\Admin\Metabox;

use AlexaCRM\WordpressCRM\Admin\Tab;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Implements data-binding metabox.
 */
class DataBinding {

    /**
     * DataBinding constructor.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'registerMetaboxes' ] );
        add_action( 'save_post', [ $this, 'savePostData' ] );

        add_action( 'wp_ajax_retrieve_entity_keys', [ $this, 'retrieve_entity_keys' ] );
        add_action( 'wp_ajax_nopriv_retrieve_entity_keys', [ $this, 'retrieve_entity_keys' ] );
    }

    /**
     * Handles the AJAX request to retrieve entity alternate keys.
     */
    public function retrieve_entity_keys() {
        $request = ACRM()->request->request;

        if ( !$request->has( 'entityLogicalName' ) ) {
            wp_send_json_error();
        }

        $entityLogicalName = $request->get( 'entityLogicalName' );
        $entity            = ASDK()->entity( $entityLogicalName );

        $entityKeys = $entity->metadata()->keys;
        wp_send_json_success( $entityKeys );
    }

    /**
     * Add our custom meta box to all support post types
     */
    public function registerMetaboxes() {
        $enabledPostTypes = get_option( 'wordpresscrm_custom_post_types', [] );
        $supportedPostTypes  = apply_filters( 'wp_access_supported_pages', array_merge( [
            'page',
            'post'
        ], $enabledPostTypes ) );

        foreach ( $supportedPostTypes as $postType ) {
            add_meta_box(
                'wordpresscrm_databinding_meta',
                __( 'Dynamics 365 Data Binding', 'integration-dynamics' ),
                [ $this, 'render' ],
                $postType, 'side', 'high' );
        }
    }

    /**
     * Display the various per-page/per-post options in the sidebar
     */
    public function render() {
        global $post;

        if ( ACRM()->connected() ) {

            $entities = Tab::get_all_entities();

            $bindingConfig = ACRM()->getBinding()->getPostBinding( $post->ID );

            $post_entity         = $bindingConfig['entity'];
            $post_parametername  = $bindingConfig['key'];
            $post_isdefaultview  = $bindingConfig['default'];
            $post_querystring    = $bindingConfig['query'];
            $post_empty_behavior = $bindingConfig['empty'];

            if ( $post_isdefaultview || $post_isdefaultview === 'true' ) {
                $post_isdefaultview = 'checked="checked"';
            }

            include( WORDPRESSCRM_DIR . '/views/admin/meta_box.php' );
        }
    }

    /**
     * Saves our custom meta data for the post being saved
     *
     * @param int $postId
     *
     * @return mixed
     */
    public function savePostData( $postId ) {
        $request = ACRM()->request->request;
        $postId = intval( $postId );

        // Security check
        if ( !wp_verify_nonce( $request->get( 'wordpresscrm_databinding_nonce', '' ), 'wordpresscrm_databinding' ) ) {
            return $postId;
        }

        // Meta data isn't transmitted during autosaves so don't do anything
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $postId;
        }

        $entityName = $request->get( 'wordpresscrm_databinding_entity' );

        if ( $entityName === null ) {
            ACRM()->getBinding()->updateBinding( $postId, null );

            return $postId;
        }

        $config = [
            'entity' => $entityName,
            'key' => $request->get( 'wordpresscrm_databinding_parametername', '' ),
            'query' => $request->get( 'wordpresscrm_databinding_querystring', '' ),
            'default' => ( $request->get( 'wordpresscrm_databinding_isdefaultview' ) === 'true' ),
            'empty' => $request->get( 'wordpresscrm_databinding_empty_behavior', '' ),
            'post' => $postId,
        ];

        ACRM()->getBinding()->updateBinding( $postId, $config );

        if ( $config['default'] ) {
            ACRM()->getBinding()->updateDefaultBinding( $config['entity'], $postId );
        }

        return $postId;
    }

}
