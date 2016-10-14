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

        $args = array(
            "error" => $error,
        );

        return Template::printTemplate( "error.php", $args );
    }

    public static function returnExceptionError( $exception = null ) {
        $args = array(
            'error' => __( 'An error occurred, please try again later or contact site administration', 'integration-dynamics' ),
            'exception' => $exception,
        );

        return Template::printTemplate( "exception.php", $args );
    }

    public static function notConnected() {
        return self::returnError( __( "Wordpress CRM Plugin is not connected to Dynamics CRM", 'integration-dynamics' ) );
    }

    /* "{key}, {key}" */
    public static function parseKeyArrayShortcodeAttribute( $paramsAttibute = null ) {
        $params = Array();
        /* Check default fields */
        if ( $paramsAttibute ) {

            $paramsAttibute = preg_replace( "/[{}]/", "", $paramsAttibute );

            if ( strstr( $paramsAttibute, ',' ) ) {
                $params = explode( ',', $paramsAttibute );
            } else {
                $params = array( 0 => $paramsAttibute );
            }
        }

        return $params;
    }

    /* "{key:value}, {key:value}" */
    public static function parseKeyValueArrayShortcodeAttribute( $defaultValues ) {
        $default = array();
        /* Check default fields */
        if ( $defaultValues ) {
            /* Remove enclosing braces */
            $array = array_filter( preg_split( "/[{}]/", $defaultValues ) );
            /* Extract field name and default values */
            foreach ( $array as $arr ) {
                $temp                = explode( ":", $arr );
                $default[ $temp[0] ] = $temp[1];
            }
            /* Remove empty field names */
            if ( isset( $default[""] ) ) {
                unset( $default[""] );
            }
            /* Remove empty default values */
            $default = array_filter( $default );
            /* Set variable to NULL if array is empty */
            if ( empty( $default ) ) {
                $default = array();
            }
        }

        return $default;
    }

}
