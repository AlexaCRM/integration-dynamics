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

    /**
     * @param string $error
     *
     * @return string
     */
    public static function returnError( $error ) {
        $args = [
            'error' => $error,
        ];

        return ACRM()->getTemplate()->printTemplate( "error.php", $args );
    }

    /**
     * @param \Exception $exception
     *
     * @return string
     */
    public static function returnExceptionError( $exception = null ) {
        $args = [
            'error' => __( 'An error occurred, please try again later or contact site administration', 'integration-dynamics' ),
            'exception' => $exception,
        ];

        return ACRM()->getTemplate()->printTemplate( "exception.php", $args );
    }

    /**
     * @return string
     */
    public static function notConnected() {
        return self::returnError( Messages::getMessage( 'general', 'not_connected' ) );
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

    /**
     * Reverses partially effects of wpautop().
     *
     * @param string $content
     *
     * @return string
     */
    public static function reverse_wpautop( $content ) {
        //remove any new lines already in there
        $content = str_replace( "\n", "", $content );

        //remove all <p>
        $content = str_replace( "<p>", "", $content );

        //replace <br /> with \n
        $content = str_replace( [ "<br />", "<br>", "<br/>" ], "\n", $content );

        //replace </p> with \n\n
        $content = str_replace( "</p>", "\n\n", $content );

        //replace quotes
        $content = str_replace( [ '&#8220;', '&#8221;' ], '"', $content );

        return $content;
    }

}
