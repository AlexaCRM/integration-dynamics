<?php

namespace AlexaCRM\WordpressCRM;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Provides access to templates
 *
 * @package AlexaCRM\WordpressCRM
 */
class Template {

    /**
     * Prints or returns template body.
     *
     * @param string $template_name
     * @param array $args Array of variables to make available inside the template
     * @param string $template_path Path to the template
     * @param string $default_path Default path to the template
     * @param bool $echo Output the template to browser if true, return it as string otherwise
     *
     * @return string|void
     */
    public static function printTemplate( $template_name, $args = array(), $template_path = '', $default_path = '', $echo = false ) {
        ob_start();
        Template::getTemplate( $template_name, $args, $template_path, $default_path );
        if ( $echo ) {
            echo ob_get_clean();
        } else {
            return ob_get_clean();
        }
    }

    /**
     * Prints the template body.
     *
     * @param string $template_name
     * @param array $args Array of variables to make available inside the template
     * @param string $template_path Path to the template
     * @param string $default_path Default path to the template
     */
    public static function getTemplate( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
        if ( $args && is_array( $args ) ) {
            extract( $args );
        }

        $located = Template::locateTemplate( $template_name, $template_path, $default_path );

        if ( !file_exists( $located ) ) {
            _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '1.0' );

            return;
        }

        // Allow 3rd party plugin filter template file from their plugin
        $located = apply_filters( 'wordpresscrm_get_template', $located, $template_name, $args, $template_path, $default_path );

        do_action( 'wordpresscrm_before_template_part', $template_name, $template_path, $located, $args );

        include( $located );

        do_action( 'wordpresscrm_after_template_part', $template_name, $template_path, $located, $args );
    }

    /**
     * Locates the template file.
     *
     * @param string $template_name
     * @param string $template_path
     * @param string $default_path
     *
     * @return string Full path to the template file
     */
    public static function locateTemplate( $template_name, $template_path = '', $default_path = '' ) {
        if ( !$template_path ) {
            /**
             * Filters the default path for templates.
             *
             * @param string $path Template path with a trailing slash.
             */
            $template_path = apply_filters( 'wordpress_template_path', 'wordpress-crm/' );
        }

        if ( !$default_path ) {
            $default_path = WORDPRESSCRM_DIR . '/templates/';
        }

        // Look within passed path within the theme - this is priority
        $template = locate_template(
            array(
                trailingslashit( $template_path ) . $template_name,
                $template_name
            )
        );

        // Get default template
        if ( !$template ) {
            $template = $default_path . $template_name;
        }

        // Return what we found
        return apply_filters( 'wordpresscrm_locate_template', $template, $template_name, $template_path );
    }

    /**
     * Constructs the path to the shortcode template.
     *
     * @param string $path
     * @param string $firstPart
     * @param string $secondPart
     * @param string $thirdPart
     *
     * @return string
     */
    public static function locateShortcodeTemplate( $path, $firstPart = null, $secondPart = null, $thirdPart = null ) {

        $pathFirst            = ( $firstPart ) ? $path . "-" . strtolower( str_replace( " ", "-", $firstPart ) ) : null;
        $pathFirstSecond      = ( $secondPart ) ? $pathFirst . "-" . str_replace( " ", "-", strtolower( $secondPart ) ) : null;
        $pathFirstSecondThird = ( $thirdPart ) ? $pathFirstSecond . "-" . str_replace( " ", "-", strtolower( $thirdPart ) ) : null;

        if ( $pathFirstSecondThird && file_exists( Template::locateTemplate( $pathFirstSecondThird . ".php" ) ) ) {
            return $pathFirstSecondThird . ".php";
        }
        if ( $pathFirstSecond && file_exists( Template::locateTemplate( $pathFirstSecond . ".php" ) ) ) {
            return $pathFirstSecond . ".php";
        }
        if ( $pathFirst && file_exists( Template::locateTemplate( $pathFirst . ".php" ) ) ) {
            return $pathFirst . ".php";
        }

        return $path . ".php";
    }

}
