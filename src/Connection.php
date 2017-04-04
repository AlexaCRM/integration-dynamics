<?php

namespace AlexaCRM\WordpressCRM;

use Exception;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Connection status checker
 *
 * @package AlexaCRM\WordpressCRM
 */
class Connection {

    /**
     * Cached connection status
     *
     * @var bool
     */
    protected static $connectionStatus = null;

    /**
     * Checks whether connection to CRM is established and in working condition
     *
     * @return bool
     */
    public static function checkConnection() {
        if ( ACRM()->connected() && static::$connectionStatus === null ) {
            try {
                $cacheKey = 'wpcrm_whoami';
                $cache    = ACRM()->getCache();

                if ( !$cache->exists( $cacheKey ) ) {
                    ASDK()->executeAction( "WhoAmI" );
                    $cache->set( $cacheKey, true, 10 * 60 );
                }

                static::setConnectionStatus( true );
            } catch ( Exception $ex ) {
                self::setConnectionStatus( false );

                try {
                    ACRM()->purgeCache();
                } catch ( Exception $e ) {
                    // nop
                }
            }
        }

        return static::$connectionStatus;
    }

    /**
     * Updates the connection status with a given value
     *
     * @param bool $status
     */
    public static function setConnectionStatus( $status ) {
        $options              = get_option( Plugin::PREFIX . 'options' );
        $options["connected"] = $status;
        update_option( Plugin::PREFIX . 'options', $options );
        static::$connectionStatus = $status;

        // Update plugin in-memory options
        ACRM()->options = $options;
    }

}
