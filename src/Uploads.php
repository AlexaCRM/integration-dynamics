<?php

namespace AlexaCRM\WordpressCRM;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Uploads {

    public static function uploadDir( $type = false ) {
        $uploads = wp_upload_dir();

        $uploads = apply_filters( 'wordpresscrm_upload_dir', array(
            'dir' => $uploads['basedir'],
            'url' => $uploads['baseurl']
        ) );

        if ( 'dir' == $type ) {
            return $uploads['dir'];
        }
        if ( 'url' == $type ) {
            return $uploads['url'];
        }

        return $uploads;
    }

    public static function uploadTmpDir() {
        if ( defined( 'WORDPRESSCRM_UPLOADS_TMP_DIR' ) ) {
            return WORDPRESSCRM_UPLOADS_TMP_DIR;
        } else {
            return self::uploadDir( 'dir' ) . '/wordpresscrm_uploads';
        }
    }

    public static function maybeAddRandomDir( $dir ) {
        do {
            $rand_max = mt_getrandmax();
            $rand     = zeroise( mt_rand( 0, $rand_max ), strlen( $rand_max ) );
            $dir_new  = path_join( $dir, $rand );
        } while ( file_exists( $dir_new ) );

        if ( wp_mkdir_p( $dir_new ) ) {
            return $dir_new;
        }

        return $dir;
    }

    /* File uploading functions */
    public static function initUploads() {
        $dir = self::uploadTmpDir();
        wp_mkdir_p( $dir );

        $htaccess_file = trailingslashit( $dir ) . '.htaccess';

        if ( file_exists( $htaccess_file ) ) {
            return;
        }

        if ( $handle = @fopen( $htaccess_file, 'w' ) ) {
            fwrite( $handle, "Deny from all\n" );
            fclose( $handle );
        }
    }

    public static function canonicalize( $text ) {
        if ( function_exists( 'mb_convert_kana' )
             && 'UTF-8' == get_option( 'blog_charset' )
        ) {
            $text = mb_convert_kana( $text, 'asKV', 'UTF-8' );
        }

        $text = strtolower( $text );
        $text = trim( $text );

        return $text;
    }

    public static function antiscriptFileName( $filename ) {
        $filename = basename( $filename );
        $parts    = explode( '.', $filename );

        if ( count( $parts ) < 2 ) {
            return $filename;
        }

        $script_pattern = '/^(php|phtml|pl|py|rb|cgi|asp|aspx)\d?$/i';

        $filename  = array_shift( $parts );
        $extension = array_pop( $parts );

        foreach ( (array) $parts as $part ) {
            if ( preg_match( $script_pattern, $part ) ) {
                $filename .= '.' . $part . '_';
            } else {
                $filename .= '.' . $part;
            }
        }

        if ( preg_match( $script_pattern, $extension ) ) {
            $filename .= '.' . $extension . '_.txt';
        } else {
            $filename .= '.' . $extension;
        }

        return $filename;
    }

    public static function upload( $file ) {
        if ( isset( $file['error'] ) && $file['error'] && UPLOAD_ERR_NO_FILE != $file['error'] ) {
            return false;
        }

        if ( !is_uploaded_file( $file['tmp_name'] ) ) {
            return false;
        }

        self::initUploads(); // Confirm upload dir
        $uploads_dir = self::uploadTmpDir();
        $uploads_dir = self::maybeAddRandomDir( $uploads_dir );

        $filename = $file['name'];
        $filename = self::canonicalize( $filename );
        $filename = sanitize_file_name( $filename );
        $filename = self::antiscriptFileName( $filename );
        $filename = wp_unique_filename( $uploads_dir, $filename );

        $new_file = trailingslashit( $uploads_dir ) . $filename;

        if ( false === @move_uploaded_file( $file['tmp_name'], $new_file ) ) {
            return false;
        }

        // Make sure the uploaded file is only readable for the owner process
        @chmod( $new_file, 0400 );

        return $new_file;
    }

    public static function rearrayFiles( &$file_post, $subKey ) {
        $file_ary   = array();
        $file_count = count( $file_post['name'][ $subKey ] );
        $file_keys  = array_keys( $file_post );
        for ( $i = 0; $i < $file_count; $i ++ ) {
            foreach ( $file_keys as $key ) {
                $file_ary[ $i ][ $key ] = $file_post[ $key ][ $subKey ][ $i ];
            }
        }

        return $file_ary;
    }

}
