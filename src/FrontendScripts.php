<?php

/**
 * Handle frontend forms
 *
 * @class        AlexaCRM\WordpressCRM\FrontendScripts
 * @version        0.9.11
 * @package        wordpress-crm/includes/
 * @category            Class
 * @author        AlexaCRM
 */
// Exit if accessed directly
namespace AlexaCRM\WordpressCRM;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages front-end scripts and styles that the plugin uses
 *
 * @package AlexaCRM\WordpressCRM
 */
class FrontendScripts {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'loadAssets' ), 25 );
        add_action( 'wp_print_scripts', array( $this, 'checkJquery' ), 25 );
    }

    /**
     * Get styles for the frontend
     *
     * @return array
     */
    public function getStyles() {
        return apply_filters( 'wordpresscrm_enqueue_styles', array(
            'wordpresscrm-layout'   => array(
                'src'     => ACRM()->getPluginURL() . '/resources/front/css/wordpresscrm.css',
                'deps'    => '',
                'version' => WORDPRESSCRM_VERSION,
                'media'   => 'all'
            ),
            'wordpresscrm-jquery'   => array(
                'src'     => ACRM()->getPluginURL() . '/resources/front/css/wordpresscrm-jqueryui-css.css',
                'deps'    => '',
                'version' => WORDPRESSCRM_VERSION,
                'media'   => 'all'
            ),
            'jquery-datetimepicker' => array(
                'src'     => ACRM()->getPluginURL() . '/resources/front/css/jquery.datetimepicker.css',
                'deps'    => '',
                'version' => WORDPRESSCRM_VERSION,
                'media'   => 'all'
            ),
        ) );
    }

    /**
     * Register/queue frontend scripts.
     *
     * @access public
     * @return void
     */
    public function loadAssets() {
        $suffix     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        $assetsPath = str_replace( [ 'http:', 'https:' ], '', ACRM()->getPluginURL() ) . '/resources/';
        $scriptPath = $assetsPath . 'front/js/';

        // Register any scripts for later use, or used as dependencies
        wp_register_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js' );

        // Global frontend scripts
        wp_enqueue_script( 'recaptcha' );

        wp_register_script( 'jquery-datetimepicker', $scriptPath . 'jquery.datetimepicker.js', [ 'jquery' ] );
        wp_register_script( 'wordpresscrm-front', $scriptPath . 'wordpresscrm-front' . $suffix . '.js', [ 'jquery-datetimepicker' ] );
        wp_register_script( 'jquery-validation', $scriptPath . 'jquery.validate.min.js', [ 'jquery' ] );
        wp_register_script( 'wordpresscrm-lookup-dialog', $scriptPath . 'lookup-dialog.js', [ 'jquery' ] );

        // localize scripts
        $wpcrmL10n = [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'dateformat' => get_option( 'date_format' ),
            'datetimeformat' => get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
        ];
        wp_localize_script( 'wordpresscrm-front', 'wpcrm', $wpcrmL10n );

        // CSS Styles
        $enqueuedStyles = $this->getStyles();

        if ( $enqueuedStyles ) {
            foreach ( $enqueuedStyles as $handle => $args ) {
                wp_enqueue_style( $handle, $args['src'], $args['deps'] );
            }
        }
    }

    /**
     * Alexa WPSDK requires jQuery 1.8 since it uses functions like .on() for events and .parseHTML.
     * If, by the time wp_print_scrips is called, jQuery is outdated (i.e not
     * using the version in core) we need to deregister it and register the
     * core version of the file.
     *
     * @access public
     * @return void
     */
    public function checkJquery() {
        $wpScripts = wp_scripts();

        // Enforce minimum version of jQuery
        if ( !empty( $wpScripts->registered['jquery']->ver ) && !empty( $wpScripts->registered['jquery']->src ) && 0 >= version_compare( $wpScripts->registered['jquery']->ver, '1.8' ) ) {
            wp_deregister_script( 'jquery' );
            wp_register_script( 'jquery', includes_url( 'js/jquery/jquery.js' ), array(), '1.8' );
            wp_enqueue_script( 'jquery' );
        }
    }

}
