<?php

namespace AlexaCRM\WordpressCRM\Cache;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WPCache {

    public function set( $name, $value, $time_in_second = 600, $skip_if_existing = false ) {
        if ( false === ( $cache = get_transient( $name ) ) ) {
            return set_transient( $name, $value, $time_in_second );
        } elseif ( $skip_if_existing == false ) {
            return set_transient( $name, $value, $time_in_second );
        } else {
            return false;
        }
    }

    public function get( $name ) {

        $value = get_transient( $name );

        if ( false === $value ) {
            return null;
        } else {
            return $value;
        }
    }

    public function delete( $name ) {
        delete_transient( $name );
    }

    /**
     * @return bool
     * @deprecated
     */
    public function cleanup() {
        return $this->purge();
    }

    public function purge() {
        return wp_cache_flush();
    }
}
