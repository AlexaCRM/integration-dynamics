<?php

namespace AlexaCRM\WordpressCRM;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Mints and verifies per-entity-type nonces authorizing the Lookup Dialog AJAX endpoints
 * to query a given CRM entity type.
 *
 * @package AlexaCRM\WordpressCRM
 */
class LookupNonce {

    /**
     * Nonce action prefix. The entity's logical name is appended to scope the nonce to it.
     *
     * @const string
     */
    const ACTION_PREFIX = 'wpcrm_lookup_';

    /**
     * Creates a nonce authorizing lookup requests against the given entity type.
     *
     * @param string $entityLogicalName
     *
     * @return string
     */
    public static function create( $entityLogicalName ) {
        return wp_create_nonce( self::action( $entityLogicalName ) );
    }

    /**
     * Verifies a nonce was issued for the given entity type.
     *
     * @param mixed $nonce
     * @param string $entityLogicalName
     *
     * @return bool
     */
    public static function verify( $nonce, $entityLogicalName ) {
        if ( !is_string( $nonce ) || $nonce === '' || !is_string( $entityLogicalName ) || $entityLogicalName === '' ) {
            return false;
        }

        return (bool) wp_verify_nonce( $nonce, self::action( $entityLogicalName ) );
    }

    /**
     * Builds the nonce action string scoped to the given entity type.
     *
     * @param string $entityLogicalName
     *
     * @return string
     */
    private static function action( $entityLogicalName ) {
        return self::ACTION_PREFIX . strtolower( (string) $entityLogicalName );
    }
}
