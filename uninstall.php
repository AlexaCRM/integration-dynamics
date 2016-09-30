<?php

/**
 * WordpressCRM Uninstall
 *
 * Uninstalling WordpressCRM deletes user roles, options, and posts meta.
 *
 * @category    Core
 * @package    WordpressCRM/Uninstaller
 * @version     0.9.4
 */
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

delete_option( 'msdyncrm_options' );

delete_option( 'msdyncrm_oauth' );

delete_option( 'msdyncrm_registration' );

delete_option( 'msdyncrm_messages' );

delete_option( 'msdyncrm_attachments' );

delete_option( 'msdyncrm_roles' );

delete_option( 'msdyncrm_forms' );

delete_option( 'msdyncrm_views' );

delete_option( "msdyncrm_license" );

delete_option( "msdyncrm_support_report_a_bug" );

delete_option( "wordpresscrm_manual_license" );

delete_option( 'external_updates-wordpress-crm' );

/* Delete databinding entities from posts and pages */
$args  = array(
    'post_type'  => array( 'page', 'post' ),
    'meta_query' => array(
        array(
            'key' => '_wordpresscrm_databinding_entity'
        ),
        array(
            'key' => '_wordpresscrm_databinding_parametername'
        )
    )
);
$posts = get_posts( $args );

foreach ( $posts as $post ) {
    delete_post_meta( $post->ID, '_wordpresscrm_databinding_entity' );
}

/* Delete databinding parameternames from posts and pages */
$args  = array(
    'post_type'  => array( 'page', 'post' ),
    'meta_query' => array(
        array(
            'key' => '_wordpresscrm_databinding_parametername'
        )
    )
);
$posts = get_posts( $args );

foreach ( $posts as $post ) {
    delete_post_meta( $post->ID, '_wordpresscrm_databinding_parametername' );
}
