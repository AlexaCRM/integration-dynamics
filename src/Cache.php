<?php

namespace AlexaCRM\WordpressCRM;

use AlexaCRM\CRMToolkit\CacheInterface;
use AlexaCRM\WordpressCRM\Cache\PhpFastCache;
use AlexaCRM\WordpressCRM\Cache\WPCache;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Cache wrapper
 *
 * @package AlexaCRM\WordpressCRM
 */
class Cache implements CacheInterface {

    /**
     * @var PhpFastCache|WPCache
     */
    private $storage = null;

    /**
     * @var Cache
     */
    protected static $_instance = null;

    /**
     * Cache lifetime in seconds
     *
     * @var integer $cacheTime time in seconds cache will be expired
     */
    public static $cacheTime = 28800;

    /**
     * @param array $options
     *
     * @return Cache
     */
    public static function instance( $options = null ) {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self( $options );
        }

        return self::$_instance;
    }

    /**
     * Cache constructor.
     *
     * @param array $options
     */
    public function __construct( $options = null ) {
        if ( function_exists( 'wp_using_ext_object_cache' ) && wp_using_ext_object_cache() ) {
            $this->storage = new WPCache( $options );
        } else {
            PhpFastCache::$server = array( array( $options["server"], $options["port"] ) );
            $this->storage        = new PhpFastCache( $options );
        }
    }

    /**
     * Get the cache time value
     *
     * @return int cache data lifetime in seconds
     */
    public static function getCacheTime() {
        return self::$cacheTime;
    }

    /**
     * Set the cache time value
     *
     * @param int $_cacheTime cache data lifetime in seconds
     */
    public static function setCacheTime( $_cacheTime ) {
        if ( !is_int( $_cacheTime ) ) {
            return;
        }
        self::$cacheTime = $_cacheTime;
    }

    /**
     * Stores the value in cache by its key name
     *
     * @param string $key Cache item key
     * @param mixed $value Cache item value
     * @param int $expiresAfter Time in seconds to invalidate the item after
     *
     * @return mixed|null
     */
    public function set( $key, $value, $expiresAfter = null ) {
        if ( $value ) {
            $expiresAfter = ( $expiresAfter == null ) ? self::$cacheTime : $expiresAfter;

            return $this->storage->set( $key, $value, $expiresAfter );
        } else {
            return null;
        }
    }

    /**
     * Retrieves a value from cache by its key name
     *
     * @param string $key Cache item key
     * @param null $default Default value if key doesn't exist
     *
     * @return mixed|null
     */
    public function get( $key, $default = null ) {
        $value = $this->storage->get( $key );
        if ( $value == null ) {
            $value = $default;
        }

        return $value;
    }

    /**
     * Deletes a key from cache.
     *
     * @param string $key
     */
    public function delete( $key ) {
        $this->storage->delete( $key );
    }

    /**
     * Checks whether given cache key exists and is valid
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists( $key ) {
        $cacheValue = $this->storage->get( $key );

        return $cacheValue !== null;
    }

    /**
     * Clean up cache storage
     *
     * @return bool
     */
    public function cleanup() {
        return $this->storage->purge();
    }
}
