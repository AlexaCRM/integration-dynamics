<?php

namespace AlexaCRM\WordpressCRM;

class PasswordHandler {

    const CRYPTO_METHOD = 'aes-256-cbc';

    protected $key;

    public function __construct() {
        if ( defined( 'WPCRM_KEY' ) && strlen( base64_decode( WPCRM_KEY ) ) >= 32 ) {
            $this->key = base64_decode( WPCRM_KEY );
        } elseif ( defined( 'AUTH_KEY' ) && strlen( AUTH_KEY ) >= 32 ) {
            $this->key = substr( AUTH_KEY, 0, 32 );
        } else {
            $this->key = base64_decode( 'qftZWrgbk7xpp41WIQrpmvM4BQnLqxIbqTmWxA9JAFE=' );
        }
    }

    public function encrypt( $password ) {
        if ( !function_exists( 'openssl_random_pseudo_bytes' ) ) {
            return $password;
        }

        $ivLength = openssl_cipher_iv_length( self::CRYPTO_METHOD );
        $iv = openssl_random_pseudo_bytes( $ivLength );

        $ciphertext = openssl_encrypt( $password, self::CRYPTO_METHOD, $this->key, 0, $iv );

        return base64_encode( $iv ) . ':' . $ciphertext ;
    }

    public function decrypt( $ivCiphertext ) {
        if ( !function_exists( 'openssl_random_pseudo_bytes' ) ) {
            return $ivCiphertext;
        }

        if ( strpos( $ivCiphertext, ':' ) === false ) {
            return $ivCiphertext;
        }

        list( $iv, $ciphertext ) = explode( ':', $ivCiphertext );

        $password = openssl_decrypt( $ciphertext, self::CRYPTO_METHOD, $this->key, 0, base64_decode( $iv ) );
        if ( $password === false ) {
            return '_';
        }

        return $password;
    }

}
