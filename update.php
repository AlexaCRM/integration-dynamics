<?php

/**
 * WordpressCRM Update
 *
 * Updates the deprecated options, and posts meta.
 *
 * @category    Core
 * @package    WordpressCRM/Uninstaller
 * @version     1.0.35
 */
use AlexaCRM\WordpressCRM\Plugin;

/**
 * Main update class, used to update the options, pages metadata and other database things that was changed
 * in the new plugin version
 */
class WordpressCRM_Update {

    /**
     * Add actions to call the update function on the plugin load
     */
    public function __construct() {
        add_action( "wordpresscrm_before_load", array( $this, "updateDeprecatedOptions" ), 1 );
        add_action( 'wordpresscrm_before_load', array( $this, 'updateDataBoundPages' ), 9 );
    }

    /**
     * Updates the deprecated options with "alexacrm_" and "wordpresscrm_" prefix to the new "msdyncrm_" prefix.
     * Also changes general options structure that's needed for PHP CRM Toolkit constructor
     */
    public function updateDeprecatedOptions() {
        /* Update the deprecated alexacrm_options */
        if ( $options = get_option( "alexacrm_options", null ) ) {
            /* Change the old alexacrm_options structure ti the new one */
            $newOptions = array(
                "discoveryUrl"           => $options["discovery_url"],
                "username"               => $options["crmadmin_login"],
                "password"               => $options["crmadmin_password"],
                "organizationUrl"        => $options["organization_url"],
                "loginUrl"               => $options["crm_loginurl"],
                "serverUrl"              => $options["server"],
                "authMode"               => $options["authMode"],
                "crmRegion"              => $options["region"],
                "port"                   => $options["port"],
                "useSsl"                 => $options["use_ssl"],
                "organizationDataUrl"    => $options["organizationdata_url"],
                "organizationName"       => $options["organizationName"],
                "organizationUniqueName" => $options["organizationUniqueName"],
                "organizationId"         => $options["organizationId"],
                "organizationVersion"    => $options["organizationVersion"],
                'solution'               => $options["solution"],
                'solution_name'          => $options["solution_name"],
                'solution_version'       => $options["solution_version"],
                'solution_publisher'     => $options["solution_publisher"],
                "cache"                  => array( "server" => "localhost", "port" => 11211 ),
                "connected"              => $options["connected"],
            );
            /* Update the deprecated options with new prefix WORDPRESSCRM_PLUGIN_PREFIX */
            if ( update_option( Plugin::PREFIX . "options", $newOptions ) ) {
                delete_option( "alexacrm_options" );
            }
            /* Update the deprecated alexacrm_forms_options */
            $formOptions = get_option( "alexacrm_forms_options" );
            if ( $formOptions ) {
                update_option( Plugin::PREFIX . "forms", $formOptions );
                delete_option( "alexacrm_forms_options" );
            }
            /* Update the deprecated alexacrm_oauth_options */
            $oauthOptions = get_option( "alexacrm_oauth_options" );
            if ( $oauthOptions ) {
                update_option( Plugin::PREFIX . "oauth", $oauthOptions );
                delete_option( "alexacrm_oauth_options" );
            }
            /* Update the deprecated wordpresscrm_registration_options */
            $registrationOptions = get_option( "wordpresscrm_registration_options" );
            if ( $registrationOptions ) {
                update_option( Plugin::PREFIX . "registration", $registrationOptions );
                delete_option( "wordpresscrm_registration_options" );
            }
            /* Update the deprecated alexacrm_role_options */
            $rolesOptions = get_option( "alexacrm_role_options" );
            if ( $rolesOptions ) {
                update_option( Plugin::PREFIX . "roles", $rolesOptions );
                delete_option( "alexacrm_role_options" );
            }
            /* Update the deprecated wordpresscrm_search_options */
            $searchOptions = get_option( "wordpresscrm_search_options" );
            if ( $searchOptions ) {
                update_option( Plugin::PREFIX . "search", $searchOptions );
                delete_option( "wordpresscrm_search_options" );
            }
        }
    }

    /**
     * Update the deprecated posts and pages meta settings for the data-binding pages
     */
    public static function updateDataBoundPages() {
        /* Construct arguments to retrieve the posts and pages with data-binding deprecated prefix */
        $args = array(
            'post_type'  => array( 'page', 'post' ),
            'meta_query' => array(
                array(
                    'key' => '_alexacrm_databinding_entity'
                ),
                array(
                    'key' => '_alexacrm_databinding_isdefaultview'
                )
            ),
            'posts_per_page' => -1,
        );
        /* Retrieve the posts that contain deprecated meta prefixes */
        if ( $posts = get_posts( $args ) ) {

            foreach ( $posts as $post ) {
                $post_entity        = maybe_unserialize( get_post_meta( $post->ID, '_alexacrm_databinding_entity', true ) );
                $post_parametername = maybe_unserialize( get_post_meta( $post->ID, '_alexacrm_databinding_parametername', true ) );
                $post_isdefaultview = maybe_unserialize( get_post_meta( $post->ID, '_alexacrm_databinding_isdefaultview', true ) );
                $post_querystring   = maybe_unserialize( get_post_meta( $post->ID, '_alexacrm_databinding_querystring', true ) );
                /* Update the post "data-binding entity" parameter and remove old meta record */
                if ( $post_entity ) {
                    update_post_meta( $post->ID, '_wordpresscrm_databinding_entity', $post_entity );
                    delete_post_meta( $post->ID, '_alexacrm_databinding_entity' );
                }
                /* Update the post "data-binding parametername" parameter and remove old meta record */
                if ( $post_parametername ) {
                    update_post_meta( $post->ID, '_wordpresscrm_databinding_parametername', $post_parametername );
                    delete_post_meta( $post->ID, '_alexacrm_databinding_parametername' );
                }
                /* Update the post "data-binding default view" parameter and remove old meta record */
                if ( $post_isdefaultview ) {
                    update_post_meta( $post->ID, '_wordpresscrm_databinding_isdefaultview', $post_isdefaultview );
                    delete_post_meta( $post->ID, '_alexacrm_databinding_isdefaultview' );
                }
                /* Update the post "data-binding query string" parameter and remove old meta record */
                if ( $post_querystring ) {
                    update_post_meta( $post->ID, '_wordpresscrm_databinding_querystring', $post_querystring );
                    delete_post_meta( $post->ID, '_alexacrm_databinding_querystring' );
                }
            }
        }
    }

}

/* Create the instance of the WordpressCRM_Update class to initiate update */
new WordpressCRM_Update();
