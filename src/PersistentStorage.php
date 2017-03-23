<?php

namespace AlexaCRM\WordpressCRM;

use AlexaCRM\CRMToolkit\StorageInterface;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Implements a persistent storage for various types of objects
 *
 * @package AlexaCRM\WordpressCRM
 */
class PersistentStorage implements StorageInterface {

    /**
     * Storage name
     *
     * @var string
     */
    protected $storageName;

    /**
     * PersistentStorage constructor.
     *
     * @param string $storageName
     */
    public function __construct( $storageName = 'default' ) {
        if ( $storageName === 'cache' ) {
            throw new \InvalidArgumentException( 'Storage name "cache" is not allowed.' );
        }

        $this->storageName = $storageName;
    }

    /**
     * Retrieves a value from cache by key
     *
     * @param string $key Cache item key
     * @param mixed $default Default value if not found
     *
     * @return mixed
     */
    public function get( $key, $default = null ) {
        $keyPath = $this->getKeyPath( $key );
        if ( !is_readable( $keyPath ) ) {
            return $default;
        }

        $rawContents = file_get_contents( $keyPath );
        $value       = unserialize( $rawContents );

        return $value;
    }

    /**
     * Saves a value in cache by key
     *
     * @param string $key Cache item key
     * @param mixed $value Cache item value
     *
     * @return void
     */
    public function set( $key, $value ) {
        $keyPath = $this->getKeyPath( $key );

        $this->makePathForKey();

        $serializedValue = serialize( $value );
        file_put_contents( $keyPath, $serializedValue );
    }

    /**
     * Checks whether given cache key exists and is valid
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists( $key ) {
        $keyPath = $this->getKeyPath( $key );

        return is_readable( $keyPath );
    }

    /**
     * Purges cache storage
     *
     * @return void
     */
    public function cleanup() {
        $storagePath  = $this->getStoragePath();
        $storageFiles = glob( $storagePath . '/*' );

        foreach ( $storageFiles as $storageFile ) {
            if ( is_file( $storageFile ) ) {
                unlink( $storageFile );
            }
        }
    }

    /**
     * Returns the path to the given key
     *
     * @param string $key
     *
     * @return string
     */
    private function getKeyPath( $key ) {
        return $this->getStoragePath() . '/' . $key;
    }

    /**
     * Makes sure storage path exists
     *
     * @return void
     */
    private function makePathForKey() {
        $storagePath = $this->getStoragePath();
        if ( !file_exists( $storagePath ) ) {
            mkdir( $storagePath );
        }
    }

    /**
     * @return string
     */
    private function getStoragePath() {
        return WORDPRESSCRM_STORAGE . '/' . $this->storageName;
    }

    /**
     * Deletes the key from the storage.
     *
     * @param string $key Cache item key
     *
     * @return void
     */
    public function delete( $key ) {}
}
