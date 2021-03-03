<?php
/**
 * phpFastCache Copyright (c) 2016
 * http://www.phpfastcache.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace AlexaCRM\WordpressCRM\Cache;

use Exception;
use Memcache;
use Memcached;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * This class used to work with cached entities data
 *
 * @ignore
 */
class PhpFastCache {

    // Public OPTIONS
    // Can be set by phpFastCache::$option_name = $value|array|string
    /**
     * @ignore
     */
    public static $storage = "auto"; // Auto | Files | memcache | apc | wincache

    /**
     * @ignore
     */
    public static $files_cleanup_after = 1; // hour | auto clean up files after this

    /**
     * @ignore
     */
    public static $autosize = 40; // Megabytes

    /**
     * @ignore
     */
    public static $path = WORDPRESSCRM_STORAGE; // PATH/TO/CACHE/ default will be current path

    /**
     * @ignore
     */
    public static $securityKey = "cache"; // phpFastCache::$securityKey = "newKey";

    /**
     * @ignore
     */
    public static $securityHtAccess = true; // auto create .htaccess

    /**
     * @ignore
     */
    public static $option = array();

    /**
     * @ignore
     */
    public static $server = array( array( "localhost", 11211 ) ); // for MemCache

    /**
     * @ignore
     */
    public static $useTmpCache = false; // use for get from Tmp Memory, will be faster in checking cache on LOOP.

    /**
     * @ignore
     */
    private static $Tmp = array();

    /**
     * @ignore
     */
    private static $supported_api = array(
        "files",
        "memcache",
        "memcached",
        "apc",
        "wincache"
    );

    /**
     * @ignore
     */
    public static $sys = array();

    /**
     * @ignore
     */
    private static $checked = array(
        "path"        => false,
        "servers"     => array(),
        "config_file" => "",
    );

    /**
     * @ignore
     */
    private static $objects = array(
        "memcache"  => "",
        "memcached" => "",
    );

    /**
     * @ignore
     */
    private static function getOS() {
        $os = array(
            "os"     => PHP_OS,
            "php"    => PHP_SAPI,
            "system" => php_uname(),
            "unique" => md5( php_uname() . PHP_OS . PHP_SAPI )
        );

        return $os;
    }

    /**
     * @ignore
     */
    public static function systemInfo() {
        // self::startDebug(self::$sys,"Check Sys",__LINE__,__FUNCTION__);

        if ( count( self::$sys ) == 0 ) {

            // self::startDebug("Start System Info");

            self::$sys['os'] = self::getOS();

            self::$sys['errors']  = array();
            self::$sys['storage'] = "";
            self::$sys['method']  = "files";
            self::$sys['drivers'] = array(
                "apc"       => false,
                "memcache"  => false,
                "memcached" => false,
                "wincache"  => false,
                "files"     => false,
            );

            // Check apc
            if ( extension_loaded( 'apc' ) && ini_get( 'apc.enabled' ) ) {
                self::$sys['drivers']['apc'] = true;
                self::$sys['storage']        = "memory";
                self::$sys['method']         = "apc";
            }

            if ( extension_loaded( 'wincache' ) && function_exists( "wincache_ucache_set" ) ) {
                self::$sys['drivers']['wincache'] = true;
                self::$sys['storage']             = "memory";
                self::$sys['method']              = "wincache";
            }

            // Check memcache
            if ( function_exists( "memcache_connect" ) ) {
                self::$sys['drivers']['memcache'] = true;

                try {
                    memcache_connect( "127.0.0.1" );
                    self::$sys['storage'] = "memory";
                    self::$sys['method']  = "memcache";
                } catch ( Exception $e ) {
                }
            }

            // Check memcached
            if ( class_exists( "memcached" ) ) {
                self::$sys['drivers']['memcached'] = true;

                try {
                    $memcached = new Memcached();
                    $memcached->addServer( "127.0.0.1", "11211" );
                    self::$sys['storage'] = "memory";
                    self::$sys['method']  = "memcached";
                } catch ( Exception $e ) {
                }
            }

            if ( is_writable( self::getPath( true ) ) ) {
                self::$sys['drivers']['files'] = true;
            }

            if ( self::$sys['storage'] == "" ) {
                self::$sys['storage'] = "disk";
                self::$sys['method']  = "files";
            }

            if ( self::$sys['storage'] == "disk" && !is_writable( self::getPath() ) ) {
                self::$sys['errors'][] = "Please Create & CHMOD 0777 or any Writeable Mode for " . self::getPath();
            }
        }

        return self::$sys;
    }

    // return Folder Cache PATH
    // PATH Edit by SecurityKey
    // Auto create, Chmod and Warning
    // Revision 618
    // PHP_SAPI =  apache2handler should go to tmp
    /**
     * @ignore
     */
    private static function isPHPModule() {
        if ( PHP_SAPI == "apache2handler" ) {
            return true;
        } else {
            if ( strpos( PHP_SAPI, "handler" ) !== false ) {
                return true;
            }
        }

        return false;
    }

    // Revision 618
    // Security with .htaccess
    /**
     * @ignore
     */
    static function htaccessGen( $path = "" ) {
        if ( self::$securityHtAccess == true ) {

            if ( !file_exists( $path . "/.htaccess" ) ) {
                //   echo "write me";
                $html = "order deny, allow \r\n
deny from all \r\n
allow from 127.0.0.1";
                $f    = @fopen( $path . "/.htaccess", "w+" );
                @fwrite( $f, $html );
                @fclose( $f );
            } else {
                //   echo "got me";
            }
        }
    }

    /**
     * @ignore
     */
    private static function getPath( $skip_create = false ) {

        if ( self::$path == '' ) {
            // revision 618
            if ( self::isPHPModule() ) {
                $tmp_dir    = ini_get( 'upload_tmp_dir' ) ? ini_get( 'upload_tmp_dir' ) : sys_get_temp_dir();
                self::$path = $tmp_dir;
            } else {
                self::$path = dirname( __FILE__ );
            }
        }

        if ( $skip_create == false && self::$checked['path'] == false ) {
            if ( !file_exists( self::$path . "/" . self::$securityKey . "/" ) || !is_writable( self::$path . "/" . self::$securityKey . "/" ) ) {
                if ( !file_exists( self::$path . "/" . self::$securityKey . "/" ) ) {
                    @mkdir( self::$path . "/" . self::$securityKey . "/", 0777 );
                }
                if ( !is_writable( self::$path . "/" . self::$securityKey . "/" ) ) {
                    @chmod( self::$path . "/" . self::$securityKey . "/", 0777 );
                }
                if ( !file_exists( self::$path . "/" . self::$securityKey . "/" ) || !is_writable( self::$path . "/" . self::$securityKey . "/" ) ) {
                    die( "Sorry, Please create " . self::$path . "/" . self::$securityKey . "/ and SET Mode 0777 or any Writable Permission!" );
                }
            }

            self::$checked['path'] = true;
            // Revision 618
            self::htaccessGen( self::$path . "/" . self::$securityKey . "/" );
        }

        return self::$path . "/" . self::$securityKey . "/";
    }

    // return method automatic;
    // APC will be TOP, then Memcached, Memcache and Files
    /**
     * @ignore
     */
    public static function autoconfig( $name = "" ) {
        $cache = self::cacheMethod( $name );
        if ( $cache != "" && $cache != self::$storage && $cache != "auto" ) {
            return $cache;
        }

        if ( defined( 'WPCRM_CACHE_METHOD' ) ) {
            self::$storage = WPCRM_CACHE_METHOD;
        }

        $os = self::getOS();
        if ( self::$storage == "" || self::$storage == "auto" ) {
            if ( extension_loaded( 'apc' ) && ini_get( 'apc.enabled' ) && strpos( PHP_SAPI, "CGI" ) === false ) {
                self::$sys['drivers']['apc'] = true;
                self::$sys['storage']        = "memory";
                self::$sys['method']         = "apc";
            } else {
                // fix PATH for existing
                $reconfig = false;

                if ( file_exists( self::getPath() . "/config." . $os['unique'] . ".cache.ini" ) ) {
                    $info = self::decode( file_get_contents( self::getPath() . "/config." . $os['unique'] . ".cache.ini" ) );

                    if ( !isset( $info['value'] ) ) {
                        $reconfig = true;
                    } else {
                        $info      = $info['value'];
                        self::$sys = $info;
                    }
                } else {
                    $info = self::systemInfo();
                }

                if ( isset( $info['os']['unique'] ) ) {
                    if ( $info['os']['unique'] != $os['unique'] ) {
                        $reconfig = true;
                    }
                } else {
                    $reconfig = true;
                }

                if ( !file_exists( self::getPath() . "/config." . $os['unique'] . ".cache.ini" ) || $reconfig == true ) {
                    $info      = self::systemInfo();
                    self::$sys = $info;

                    try {
                        $f = fopen( self::getPath() . "/config." . $os['unique'] . ".cache.ini", "w+" );
                        fwrite( $f, self::encode( $info ) );
                        fclose( $f );
                    } catch ( Exception $e ) {
                        die( "Please chmod 0777 " . self::getPath() . "/config." . $os['unique'] . ".cache.ini" );
                    }
                }
            }

            self::$storage = self::$sys['method'];
        } else {

            if ( self::$storage === 'files' ) {
                self::$sys['storage'] = "disk";
            } elseif ( in_array( self::$storage, array( "apc", "memcache", "memcached", "wincache" ) ) ) {
                self::$sys['storage'] = "memory";
            } else {
                self::$sys['storage'] = "";
            }

            if ( self::$sys['storage'] == "" || !in_array( self::$storage, self::$supported_api ) ) {
                // Fall back to files.
                self::$storage = self::$sys['method'] = 'files';
                self::$sys['storage'] = 'disk';
            }

            self::$sys['method'] = strtolower( self::$storage );
        }

        if ( self::$sys['method'] == "files" ) {
            $last_cleanup = self::files_get( "last_cleanup_cache" );
            if ( $last_cleanup == null ) {
                self::files_cleanup();
                self::files_set( "last_cleanup_cache", @date( "U" ), 3600 * self::$files_cleanup_after );
            }
        }

        return self::$sys['method'];
    }

    /**
     * @ignore
     */
    private static function cacheMethod( $name = "" ) {
        $cache = self::$storage;
        if ( is_array( $name ) ) {
            $key = array_keys( $name );
            $key = $key[0];
            if ( in_array( $key, self::$supported_api ) ) {
                $cache = $key;
            }
        }

        return $cache;
    }

    /**
     * @ignore
     */
    public static function safename( $name ) {
        return strtolower( preg_replace( "/[^a-zA-Z0-9_\s\.]+/", "", $name ) );
    }

    /**
     * @ignore
     */
    private static function encode( $value, $time_in_second = "" ) {
        $value = serialize( array(
            "time"  => @date( "U" ),
            "value" => $value,
            "endin" => $time_in_second
        ) );

        return $value;
    }

    /**
     * @ignore
     */
    private static function decode( $value ) {
        $x = maybe_unserialize( $value );
        if ( $x == false ) {
            return $value;
        } else {
            return $x;
        }
    }

    /*
     * Start Public Static
     */

    /**
     * @ignore
     */
    public static function cleanup( $option = "" ) {
        $api       = self::autoconfig();
        self::$Tmp = array();

        switch ( $api ) {
            case "files":
                return self::files_cleanup( $option );
                break;
            case "memcache":
                return self::memcache_cleanup( $option );
                break;
            case "memcached":
                return self::memcached_cleanup( $option );
                break;
            case "wincache":
                return self::wincache_cleanup( $option );
                break;
            case "apc":
                return self::apc_cleanup( $option );
                break;
            default:
                return self::files_cleanup( $option );
                break;
        }
    }

    public static function purge() {
        $api       = self::autoconfig();
        self::$Tmp = array();

        switch ( $api ) {
            case 'files':
                return self::files_purge();
            case "memcache":
                return self::memcache_cleanup();
                break;
            case "memcached":
                return self::memcached_cleanup();
                break;
            case "wincache":
                return self::wincache_cleanup();
                break;
            case "apc":
                return self::apc_cleanup();
                break;
            default:
                return self::files_purge();
                break;
        }
    }

    /**
     * @ignore
     */
    public static function delete( $name = "string|array(db->item)" ) {

        $api = self::autoconfig( $name );
        if ( self::$useTmpCache == true ) {
            $tmp_name = md5( serialize( $api . $name ) );
            if ( isset( self::$Tmp[ $tmp_name ] ) ) {
                unset( self::$Tmp[ $tmp_name ] );
            }
        }

        switch ( $api ) {
            case "files":
                return self::files_delete( $name );
                break;
            case "memcache":
                return self::memcache_delete( $name );
                break;
            case "memcached":
                return self::memcached_delete( $name );
                break;
            case "wincache":
                return self::wincache_delete( $name );
                break;
            case "apc":
                return self::apc_delete( $name );
                break;
            default:
                return self::files_delete( $name );
                break;
        }
    }

    /**
     * @ignore
     */
    public static function exists( $name = "string|array(db->item)" ) {

        $api = self::autoconfig( $name );
        switch ( $api ) {
            case "files":
                return self::files_exist( $name );
                break;
            case "memcache":
                return self::memcache_exist( $name );
                break;
            case "memcached":
                return self::memcached_exist( $name );
                break;
            case "wincache":
                return self::wincache_exist( $name );
                break;
            case "apc":
                return self::apc_exist( $name );
                break;
            default:
                return self::files_exist( $name );
                break;
        }
    }

    /**
     * @ignore
     */
    public static function deleteMulti( $object = array() ) {
        $res = array();
        foreach ( $object as $driver => $name ) {
            if ( !is_numeric( $driver ) ) {
                $n    = $driver . "_" . $name;
                $name = array( $driver => $name );
            } else {
                $n = $name;
            }
            $res[ $n ] = self::delete( $name );
        }

        return $res;
    }

    /**
     * @ignore
     */
    public static function setMulti( $mname = array(), $time_in_second_for_all = 600, $skip_for_all = false ) {
        $res = array();

        foreach ( $mname as $object ) {
            $keys = array_keys( $object );

            if ( $keys[0] != "0" ) {
                $k    = $keys[0];
                $name = isset( $object[ $k ] ) ? array( $k => $object[ $k ] ) : "";
                $n    = $k . "_" . $object[ $k ];
                $x    = 0;
            } else {
                $name = isset( $object[0] ) ? $object[0] : "";
                $x    = 1;
                $n    = $name;
            }

            $value = isset( $object[ $x ] ) ? $object[ $x ] : "";
            $x ++;
            $time = isset( $object[ $x ] ) ? $object[ $x ] : $time_in_second_for_all;
            $x ++;
            $skip = isset( $object[ $x ] ) ? $object[ $x ] : $skip_for_all;
            $x ++;

            if ( $name != "" && $value != "" ) {
                $res[ $n ] = self::set( $name, $value, $time, $skip );
            }
        }

        return $res;
    }

    /**
     * @ignore
     */
    public static function set( $name, $value, $time_in_second = 600, $skip_if_existing = false ) {
        $api = self::autoconfig( $name );
        if ( self::$useTmpCache == true ) {
            $tmp_name               = md5( serialize( $api . $name ) );
            self::$Tmp[ $tmp_name ] = $value;
        }

        switch ( $api ) {
            case "files":
                return self::files_set( $name, $value, $time_in_second, $skip_if_existing );
                break;
            case "memcache":
                return self::memcache_set( $name, $value, $time_in_second, $skip_if_existing );
                break;
            case "memcached":
                return self::memcached_set( $name, $value, $time_in_second, $skip_if_existing );
                break;
            case "wincache":
                return self::wincache_set( $name, $value, $time_in_second, $skip_if_existing );
                break;
            case "apc":
                return self::apc_set( $name, $value, $time_in_second, $skip_if_existing );
                break;
            default:
                return self::files_set( $name, $value, $time_in_second, $skip_if_existing );
                break;
        }
    }

    /**
     * @ignore
     */
    public static function get( $name ) {
        $api = self::autoconfig( $name );
        if ( self::$useTmpCache == true ) {
            $tmp_name = md5( serialize( $api . $name ) );
            if ( isset( self::$Tmp[ $tmp_name ] ) ) {
                return self::$Tmp[ $tmp_name ];
            }
        }

        // for files, check it if NULL and "empty" string
        switch ( $api ) {
            case "files":
                return self::files_get( $name );
                break;
            case "memcache":
                return self::memcache_get( $name );
                break;
            case "memcached":
                return self::memcached_get( $name );
                break;
            case "wincache":
                return self::wincache_get( $name );
                break;
            case "apc":
                return self::apc_get( $name );
                break;
            default:
                return self::files_get( $name );
                break;
        }
    }

    /**
     * @ignore
     */
    public static function getMulti( $object = array() ) {
        $res = array();
        foreach ( $object as $driver => $name ) {
            if ( !is_numeric( $driver ) ) {
                $n    = $driver . "_" . $name;
                $name = array( $driver => $name );
            } else {
                $n = $name;
            }
            $res[ $n ] = self::get( $name );
        }

        return $res;
    }

    /**
     * @ignore
     */
    public static function stats() {
        $api = self::autoconfig();
        switch ( $api ) {
            case "files":
                return self::files_stats();
                break;
            case "memcache":
                return self::memcache_stats();
                break;
            case "memcached":
                return self::memcached_stats();
                break;
            case "wincache":
                return self::wincache_stats();
                break;
            case "apc":
                return self::apc_stats();
                break;
            default:
                return self::files_stats();
                break;
        }
    }

    /*
     * Begin FILES Cache Static
     * Use Files & Folders to cache
     */

    /**
     * @ignore
     */
    private static function files_exist( $name ) {
        $data = self::files_get( $name );
        if ( $data == null ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @ignore
     */
    private static function files_set( $name, $value, $time_in_second = 600, $skip_if_existing = false ) {

        $db     = self::selectDB( $name );
        $name   = $db['item'];
        $folder = $db['db'];

        $path = self::getPath();
        $tmp  = explode( "/", $folder );
        foreach ( $tmp as $dir ) {
            if ( $dir != "" && $dir != "." && $dir != ".." ) {
                $path .= "/" . $dir;
                if ( !file_exists( $path ) ) {
                    mkdir( $path, 0777 );
                }
            }
        }

        $file = $path . "/" . $name . ".c.html";

        $write = true;
        if ( file_exists( $file ) ) {
            $data = self::decode( file_get_contents( $file ) );
            if ( $skip_if_existing == true && ( (Int) $data['time'] + (Int) $data['endin'] > @date( "U" ) ) ) {
                $write = false;
            }
        }

        if ( $write == true ) {
            try {
                $f = fopen( $file, "w+" );
                fwrite( $f, self::encode( $value, $time_in_second ) );
                fclose( $f );
            } catch ( Exception $e ) {}
        }

        return $value;
    }

    /**
     * @ignore
     */
    private static function files_get( $name ) {
        $db     = self::selectDB( $name );
        $name   = $db['item'];
        $folder = $db['db'];

        $path = self::getPath();
        $tmp  = explode( "/", $folder );
        foreach ( $tmp as $dir ) {
            if ( $dir != "" && $dir != "." && $dir != ".." ) {
                $path .= "/" . $dir;
            }
        }

        $file = $path . "/" . $name . ".c.html";

        if ( !file_exists( $file ) ) {
            return null;
        }

        $data = self::decode( file_get_contents( $file ) );

        if ( !isset( $data['time'] ) || !isset( $data['endin'] ) || !isset( $data['value'] ) ) {
            return null;
        }

        if ( $data['time'] + $data['endin'] < @date( "U" ) ) {
            // exp
            unlink( $file );

            return null;
        }

        return isset( $data['value'] ) ? $data['value'] : null;
    }

    /**
     * @ignore
     */
    private static function files_stats( $dir = "" ) {
        $total = array(
            "expired" => 0,
            "size"    => 0,
            "files"   => 0
        );
        if ( $dir == "" ) {
            $dir = self::getPath();
        }
        $d = opendir( $dir );
        while ( $file = readdir( $d ) ) {
            if ( $file != "." && $file != ".." ) {
                $path = $dir . "/" . $file;
                if ( is_dir( $path ) ) {
                    $in               = self::files_stats( $path );
                    $total['expired'] = $total['expired'] + $in['expired'];
                    $total['size']    = $total['size'] + $in['size'];
                    $total['files']   = $total['files'] + $in['files'];
                } elseif ( strpos( $path, ".c.html" ) !== false ) {
                    $data = self::decode( $path );
                    if ( isset( $data['value'] ) && isset( $data['time'] ) && isset( $data['endin'] ) ) {
                        $total['files'] ++;
                        if ( $data['time'] + $data['endin'] < @date( "U" ) ) {
                            $total['expired'] ++;
                        }
                        $total['size'] = $total['size'] + filesize( $path );
                    }
                }
            }
        }
        if ( $total['size'] > 0 ) {
            $total['size'] = $total['size'] / 1024 / 1024;
        }

        return $total;
    }

    /**
     * @ignore
     */
    private static function files_cleanup( $dir = "" ) {
        $total = 0;
        if ( $dir == "" ) {
            $dir = untrailingslashit( self::getPath() );
        }
        $d = opendir( $dir );
        while ( $file = readdir( $d ) ) {
            if ( $file != "." && $file != ".." ) {
                $path = $dir . "/" . $file;
                if ( is_dir( $path ) ) {
                    $total = $total + self::files_cleanup( $path );
                    try {
                        $directoryFilesCount = count( array_diff( scandir( $path ), [ '..', '.' ] ) );
                        if ( !$directoryFilesCount ) {
                            @rmdir( $path );
                        }
                    } catch ( Exception $e ) {
                        // nothing;
                    }
                } elseif ( strpos( $path, ".c.html" ) !== false ) {
                    $data = self::decode( file_get_contents( $path ) );
                    if ( isset( $data['value'] ) && isset( $data['time'] ) && isset( $data['endin'] ) ) {
                        if ( (Int) $data['time'] + (Int) $data['endin'] < @date( "U" ) ) {
                            unlink( $path );
                            $total ++;
                        }
                    } else {
                        unlink( $path );
                        $total ++;
                    }
                }
            }
        }

        return $total;
    }

    public static function files_purge( $dir = '' ) {
        $total = 0;
        if ( $dir == "" ) {
            $dir = untrailingslashit( self::getPath() );
        }
        $d = opendir( $dir );
        while ( $file = readdir( $d ) ) {
            if ( $file != "." && $file != ".." ) {
                $path = $dir . "/" . $file;
                if ( is_dir( $path ) ) {
                    $total = $total + self::files_purge( $path );
                    try {
                        @rmdir( $path );
                    } catch ( Exception $e ) {
                        // nothing;
                    }
                } elseif ( strpos( $path, ".c.html" ) !== false ) {
                    unlink( $path );
                    $total ++;
                }
            }
        }

        return $total;
    }

    /**
     * @ignore
     */
    private static function files_delete( $name ) {
        $db     = self::selectDB( $name );
        $name   = $db['item'];
        $folder = $db['db'];

        $path = self::getPath();
        $tmp  = explode( "/", $folder );
        foreach ( $tmp as $dir ) {
            if ( $dir != "" && $dir != "." && $dir != ".." ) {
                $path .= "/" . $dir;
            }
        }

        $file = $path . "/" . $name . ".c.html";
        if ( file_exists( $file ) ) {
            try {
                unlink( $file );

                return true;
            } catch ( Exception $e ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @ignore
     */
    private static function getMemoryName( $name ) {
        $db     = self::selectDB( $name );
        $name   = $db['item'];
        $folder = $db['db'];
        $name   = $folder . "_" . $name;

        // connect memory server
        if ( self::$sys['method'] == "memcache" || $db['db'] == "memcache" ) {
            self::memcache_addserver();
        } elseif ( self::$sys['method'] == "memcached" || $db['db'] == "memcached" ) {
            self::memcached_addserver();
        } elseif ( self::$sys['method'] == "wincache" ) {
            // init WinCache here
        }

        return $name;
    }

    /*
     * Begin APC Static
     * http://www.php.net/manual/en/ref.apc.php
     */

    /**
     * @ignore
     */
    private static function apc_exist( $name ) {
        $name = self::getMemoryName( $name );
        if ( apc_exists( $name ) ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @ignore
     */
    private static function apc_set( $name, $value, $time_in_second = 600, $skip_if_existing = false ) {
        $name = self::getMemoryName( $name );
        if ( $skip_if_existing == true ) {
            return apc_add( $name, $value, $time_in_second );
        } else {
            return apc_store( $name, $value, $time_in_second );
        }
    }

    /**
     * @ignore
     */
    private static function apc_get( $name ) {

        $name = self::getMemoryName( $name );

        $data = apc_fetch( $name, $bo );

        if ( $bo === false ) {
            return null;
        }

        return $data;
    }

    /**
     * @ignore
     */
    private static function apc_stats() {
        try {
            return apc_cache_info( "user" );
        } catch ( Exception $e ) {
            return array();
        }
    }

    /**
     * @ignore
     */
    private static function apc_cleanup( $option = array() ) {
        return apc_clear_cache( "user" );
    }

    /**
     * @ignore
     */
    private static function apc_delete( $name ) {
        $name = self::getMemoryName( $name );

        return apc_delete( $name );
    }

    /*
     * Begin Memcache Static
     * http://www.php.net/manual/en/class.memcache.php
     */

    /**
     * @ignore
     */
    public static function memcache_addserver() {
        if ( !isset( self::$checked['memcache'] ) ) {
            self::$checked['memcache'] = array();
        }

        if ( self::$objects['memcache'] == "" ) {
            self::$objects['memcache'] = new Memcache();

            foreach ( self::$server as $server ) {
                $name = isset( $server[0] ) ? $server[0] : "";
                $port = isset( $server[1] ) ? $server[1] : 11211;
                if ( !in_array( $server, self::$checked['memcache'] ) && $name != "" ) {
                    self::$objects['memcache']->addServer( $name, $port );
                    self::$checked['memcache'][] = $name;
                }
            }
        }
    }

    /**
     * @ignore
     */
    private static function memcache_exist( $name ) {
        $x = self::memcache_get( $name );
        if ( $x == null ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @ignore
     */
    private static function memcache_set( $name, $value, $time_in_second = 600, $skip_if_existing = false ) {
        $orgi = $name;
        $name = self::getMemoryName( $name );
        if ( $skip_if_existing == false ) {
            return self::$objects['memcache']->set( $name, $value, false, $time_in_second );
        } else {
            return self::$objects['memcache']->add( $name, $value, false, $time_in_second );
        }
    }

    /**
     * @ignore
     */
    private static function memcache_get( $name ) {
        $name = self::getMemoryName( $name );
        $x    = self::$objects['memcache']->get( $name );
        if ( $x == false ) {
            return null;
        } else {
            return $x;
        }
    }

    /**
     * @ignore
     */
    private static function memcache_stats() {
        self::memcache_addserver();

        return self::$objects['memcache']->getStats();
    }

    /**
     * @ignore
     */
    private static function memcache_cleanup( $option = "" ) {
        self::memcache_addserver();
        self::$objects['memcache']->flush();

        return true;
    }

    /**
     * @ignore
     */
    private static function memcache_delete( $name ) {
        $name = self::getMemoryName( $name );

        return self::$objects['memcache']->delete( $name );
    }

    /*
     * Begin Memcached Static
     */

    /**
     * @ignore
     */
    public static function memcached_addserver() {
        if ( !isset( self::$checked['memcached'] ) ) {
            self::$checked['memcached'] = array();
        }

        if ( self::$objects['memcached'] == "" ) {
            self::$objects['memcached'] = new Memcached();

            foreach ( self::$server as $server ) {
                $name    = isset( $server[0] ) ? $server[0] : "";
                $port    = isset( $server[1] ) ? $server[1] : 11211;
                $sharing = isset( $server[2] ) ? $server[2] : 0;
                if ( !in_array( $server, self::$checked['memcached'] ) && $name != "" ) {
                    if ( $sharing > 0 ) {
                        self::$objects['memcached']->addServer( $name, $port, $sharing );
                    } else {
                        self::$objects['memcached']->addServer( $name, $port );
                    }

                    self::$checked['memcached'][] = $name;
                }
            }
        }
    }

    /**
     * @ignore
     */
    private static function memcached_exist( $name ) {
        $x = self::memcached_get( $name );
        if ( $x == null ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @ignore
     */
    private static function memcached_set( $name, $value, $time_in_second = 600, $skip_if_existing = false ) {
        $orgi = $name;
        $name = self::getMemoryName( $name );
        if ( $skip_if_existing == false ) {
            return self::$objects['memcached']->set( $name, $value, time() + $time_in_second );
        } else {
            return self::$objects['memcached']->add( $name, $value, time() + $time_in_second );
        }
    }

    /**
     * @ignore
     */
    private static function memcached_get( $name ) {
        $name = self::getMemoryName( $name );
        $x    = self::$objects['memcached']->get( $name );
        if ( $x == false ) {
            return null;
        } else {
            return $x;
        }
    }

    /**
     * @ignore
     */
    private static function memcached_stats() {
        self::memcached_addserver();

        return self::$objects['memcached']->getStats();
    }

    /**
     * @ignore
     */
    private static function memcached_cleanup( $option = "" ) {
        self::memcached_addserver();
        self::$objects['memcached']->flush();

        return true;
    }

    /**
     * @ignore
     */
    private static function memcached_delete( $name ) {
        $name = self::getMemoryName( $name );

        return self::$objects['memcached']->delete( $name );
    }

    /*
     * Begin WinCache Static
     */

    /**
     * @ignore
     */
    private static function wincache_exist( $name ) {
        $name = self::getMemoryName( $name );
        if ( wincache_ucache_exists( $name ) ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @ignore
     */
    private static function wincache_set( $name, $value, $time_in_second = 600, $skip_if_existing = false ) {
        $orgi = $name;
        $name = self::getMemoryName( $name );
        if ( $skip_if_existing == false ) {
            return wincache_ucache_set( $name, $value, $time_in_second );
        } else {
            return wincache_ucache_add( $name, $value, $time_in_second );
        }
    }

    /**
     * @ignore
     */
    private static function wincache_get( $name ) {
        $name = self::getMemoryName( $name );

        $x = wincache_ucache_get( $name, $suc );

        if ( $suc == false ) {
            return null;
        } else {
            return $x;
        }
    }

    /**
     * @ignore
     */
    private static function wincache_stats() {
        return wincache_scache_info();
    }

    /**
     * @ignore
     */
    private static function wincache_cleanup( $option = "" ) {
        wincache_ucache_clear();

        return true;
    }

    /**
     * @ignore
     */
    private static function wincache_delete( $name ) {
        $name = self::getMemoryName( $name );

        return wincache_ucache_delete( $name );
    }

    // For Files, return Dir
    /**
     * @ignore
     */
    private static function selectDB( $object ) {
        $res = array(
            'db'   => "",
            'item' => "",
        );
        if ( is_array( $object ) ) {
            $key         = array_keys( $object );
            $key         = $key[0];
            $res['db']   = $key;
            $res['item'] = self::safename( $object[ $key ] );
        } else {
            $res['item'] = self::safename( $object );
        }

        if ( $res['db'] == "" && self::$sys['method'] == "files" ) {
            $res['db'] = "files";
        }

        return $res;
    }

}
