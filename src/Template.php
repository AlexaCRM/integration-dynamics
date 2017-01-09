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
     * @param string $templateName
     * @param array $args Array of variables to make available inside the template
     * @param string $templatePath Path to the template
     * @param string $defaultPath Default path to the template
     *
     * @return string
     */
    public function printTemplate( $templateName, $args = [], $templatePath = '', $defaultPath = '' ) {
        ob_start();

        $this->getTemplate( $templateName, $args, $templatePath, $defaultPath );

        return ob_get_clean();
    }

    /**
     * Prints the template body.
     *
     * @param string $templateName
     * @param array $args Array of variables to make available inside the template
     * @param string $templatePath Path to the template
     * @param string $defaultPath Default path to the template
     */
    public function getTemplate( $templateName, $args = [], $templatePath = '', $defaultPath = '' ) {
        if ( $args && is_array( $args ) ) {
            extract( $args );
        }

        $located = $this->locateTemplate( $templateName, $templatePath, $defaultPath );

        if ( !file_exists( $located ) ) {
            _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '1.0' );

            return;
        }

        // Allow 3rd party plugin filter template file from their plugin
        $located = apply_filters( 'wordpresscrm_get_template', $located, $templateName, $args, $templatePath, $defaultPath );

        do_action( 'wordpresscrm_before_template_part', $templateName, $templatePath, $located, $args );

        include( $located );

        do_action( 'wordpresscrm_after_template_part', $templateName, $templatePath, $located, $args );
    }

    /**
     * Locates the template file.
     *
     * @param string $templateName
     * @param string $templatePath
     * @param string $defaultPath
     *
     * @return string Full path to the template file
     */
    public function locateTemplate( $templateName, $templatePath = '', $defaultPath = '' ) {
        if ( !$templatePath ) {
            /**
             * Filters the default path for templates.
             *
             * @param string $path Template path with a trailing slash.
             */
            $templatePath = apply_filters( 'wordpress_template_path', 'wordpress-crm/' );
        }

        if ( !$defaultPath ) {
            $defaultPath = WORDPRESSCRM_DIR . '/templates/';
        }

        // Look within passed path within the theme - this is priority
        $template = locate_template(
            array(
                trailingslashit( $templatePath ) . $templateName,
                $templateName
            )
        );

        // Get default template
        if ( !$template ) {
            $template = $defaultPath . $templateName;
        }

        // Return what we found
        return apply_filters( 'wordpresscrm_locate_template', $template, $templateName, $templatePath );
    }

    /**
     * Constructs the path to the shortcode template.
     *
     * @param string ...$path Elements of the template path.
     *
     * @return string
     */
    public function locateShortcodeTemplate( $path  ) {
        $pathSlices = func_get_args();

        // filter empty arguments
        $pathSlices = array_filter( $pathSlices );

        // templates must be lower-case, dashes instead of spaces, if any
        $pathSlices = array_map( function( $slice ) {
            return strtolower( str_replace( ' ', '-', $slice ) );
        }, $pathSlices );

        while ( count( $pathSlices ) ) {
            $possiblePath = implode( '-', $pathSlices ) . '.php';

            if ( file_exists( $this->locateTemplate( $possiblePath ) ) ) {
                return $possiblePath;
            }

            array_pop( $pathSlices );
        }

        return $path . '.php';
    }

}
