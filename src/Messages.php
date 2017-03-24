<?php

namespace AlexaCRM\WordpressCRM;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Messages {

    /**
     * Retrieves a user-defined message.
     *
     * @param $key
     * @param $value
     * @param array $args
     *
     * @return mixed|string
     */
    public static function getMessage( $key, $value, $args = [] ) {
        $options = ACRM()->option( 'messages' );

        if ( isset( $options[ $key ][ $value ] ) && $options[ $key ][ $value ] ) {
            $message = $options[ $key ][ $value ];

            if ( !empty( $args ) ) {
                foreach ( $args as $key => $value ) {
                    $message = str_replace( "%" . $key . "%", $value, $message );
                }
            }

            return $message;
        }

        ACRM()->getLogger()->warning( 'Undefined message ' . $key . '.' . $value . 'invoked.', [ 'trace' => debug_backtrace() ] );

        return '';
    }
}

