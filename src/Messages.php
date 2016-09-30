<?php

namespace AlexaCRM\WordpressCRM;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Messages {

    public static function getMessage( $key, $value, $args = [] ) {
        $options = ACRM()->option( 'messages' );

        if ( isset( $options[ $key ][ $value ] ) && $options[ $key ][ $value ] ) {
            $message = $options[ $key ][ $value ];

            if ( !empty( $args ) ) {
                foreach ( $args as $key => $value ) {
                    $message = str_replace( "%" . $key . "%", $value, $message );
                }
            }

            return __( $message, 'integration-dynamics' );
        }

        $trace = debug_backtrace();
        trigger_error( 'Undefined message with Key: ' . $key . ' Value:' . $value
                       . ' in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'], E_USER_NOTICE );

        return null;
    }
}

