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
        add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ), 25 );
        add_action( 'wp_print_scripts', array( $this, 'check_jquery' ), 25 );
    }

    /**
     * Get styles for the frontend
     *
     * @return array
     */
    public static function get_styles() {

        return apply_filters( 'wordpresscrm_enqueue_styles', array(
            'wordpresscrm-layout'   => array(
                'src'     => ACRM()->plugin_url() . '/resources/front/css/wordpresscrm.css',
                'deps'    => '',
                'version' => ACRM()->version,
                'media'   => 'all'
            ),
            'wordpresscrm-jquery'   => array(
                'src'     => ACRM()->plugin_url() . '/resources/front/css/wordpresscrm-jqueryui-css.css',
                'deps'    => '',
                'version' => ACRM()->version,
                'media'   => 'all'
            ),
            'jquery-datetimepicker' => array(
                'src'     => ACRM()->plugin_url() . '/resources/front/css/jquery.datetimepicker.css',
                'deps'    => '',
                'version' => ACRM()->version,
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
    public function load_scripts() {
        global $post, $wp;

        $suffix               = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        $assets_path          = str_replace( [ 'http:', 'https:' ], '', ACRM()->plugin_url() ) . '/resources/';
        $frontend_script_path = $assets_path . 'front/js/';

        // Register any scripts for later use, or used as dependencies
        wp_register_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js' );

        // Global frontend scripts
        wp_enqueue_script( 'recaptcha' );

        wp_register_script( 'wordpresscrm-front', $frontend_script_path . 'wordpresscrm-front' . $suffix . '.js' );
        wp_register_script( 'jquery-datetimepicker', $frontend_script_path . 'jquery.datetimepicker.js' );
        wp_register_script( 'jquery-validation', $frontend_script_path . 'jquery.validate.min.js' );

        // localize scripts
        $wpcrmFrontLocalizations = [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
        ];
        wp_localize_script( 'wordpresscrm-front', 'wpcrm', $wpcrmFrontLocalizations );

        // CSS Styles
        $enqueue_styles = $this->get_styles();

        if ( $enqueue_styles ) {
            foreach ( $enqueue_styles as $handle => $args ) {
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
    public function check_jquery() {
        global $wp_scripts;

        // Enforce minimum version of jQuery
        if ( !empty( $wp_scripts->registered['jquery']->ver ) && !empty( $wp_scripts->registered['jquery']->src ) && 0 >= version_compare( $wp_scripts->registered['jquery']->ver, '1.8' ) ) {
            wp_deregister_script( 'jquery' );
            wp_register_script( 'jquery', includes_url( 'js/jquery/jquery.js' ), array(), '1.8' );
            wp_enqueue_script( 'jquery' );
        }

        $suffix               = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        $assets_path          = str_replace( [ 'http:', 'https:' ], '', ACRM()->plugin_url() ) . '/resources/';
        $frontend_script_path = $assets_path . 'front/js/';

        wp_enqueue_script( 'wordpresscrm-front', $frontend_script_path . 'wordpresscrm-front' . $suffix . '.js' );
        wp_enqueue_script( 'jquery-datetimepicker', $frontend_script_path . 'jquery.datetimepicker.js' );
    }

}
