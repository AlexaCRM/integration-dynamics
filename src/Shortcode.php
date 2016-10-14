<?php

// Exit if accessed directly
namespace AlexaCRM\WordpressCRM;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Abstract Shortcode class
 *
 * @package AlexaCRM\WordpressCRM
 */
abstract class Shortcode {

    public abstract function shortcode( $attributes, $content = null, $tagName );

    public static function returnError( $error ) {
        $args = [
            'error' => $error,
        ];

        return Template::printTemplate( "error.php", $args );
    }

    public static function returnExceptionError( $exception = null ) {
        $args = [
            'error' => __( 'An error occurred, please try again later or contact site administration', 'integration-dynamics' ),
            'exception' => $exception,
        ];

        return Template::printTemplate( "exception.php", $args );
    }

    public static function notConnected() {
        return self::returnError( __( "Wordpress CRM Plugin is not connected to Dynamics CRM", 'integration-dynamics' ) );
    }

    /**
     * Parses the list of brackets-enclosed parameters into the array.
     *
     * Example: {arg1},{arg2},{arg3} => [ 'arg1', 'arg2', 'arg3' ]
     *
     * @param string $parameters
     *
     * @return array
     */
    public static function parseKeyArrayShortcodeAttribute( $parameters = null ) {
        if ( !$parameters ) {
            return [];
        }

        $parameters = preg_replace( '/[{}]/', '', $parameters );
        if ( strstr( $parameters, ',' ) ) {
            return explode( ',', $parameters );
        }

        return [ 0 => $parameters ];
    }

    /**
     * Parses a list of brackets-enclosed key-value parameters into the array.
     *
     * Example: {arg1:val1},{arg2:val2} => [ 'arg1' => 'val1', 'arg2' => 'val2' ]
     *
     * @param string $defaultValues
     *
     * @return array
     */
    public static function parseKeyValueArrayShortcodeAttribute( $defaultValues ) {
        if ( !$defaultValues ) {
            return [];
        }

        $default = [];

        /* Remove enclosing braces */
        $parameters = array_filter( preg_split( '/[{}]/', $defaultValues ) );

        /* Extract field name and default values */
        foreach ( $parameters as $parameter ) {
            $parameterMap = explode( ":", $parameter );

            if ( $parameterMap[0] == '' ) {
                continue;
            }

            $default[ $parameterMap[0] ] = $parameterMap[1];
        }

        /* Remove empty default values */
        $default = array_filter( $default );

        return $default;
    }

}
