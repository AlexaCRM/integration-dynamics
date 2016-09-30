<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Form;

use Exception;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class GCaptcha {

    private $captcha = null;

    public function __construct() {
        $this->captcha = ACRM()->option( "forms" );
    }

    public function __get( $name ) {
        switch ( $name ) {
            case "sitekey":
                return $this->captcha["sitekey"];
                break;
            case "enable_captcha":
                return isset( $this->captcha["enable_captcha"] )? $this->captcha["enable_captcha"] : false;
                break;
        }
    }

    public function checkResponse() {
        return ( isset( $_POST["g-recaptcha-response"] ) && $this->captcha["enable_captcha"] );
    }

    public function checkCaptcha() {

        $captchaResponse = $_POST["g-recaptcha-response"];

        $url = "https://www.google.com/recaptcha/api/siteverify?secret=" . $this->captcha["secret"] . "&response=" . $captchaResponse;

        $urlDetails = parse_url( $url );

        // setup headers
        $headers = array(
            "GET " . $urlDetails['path'] . " HTTP/1.1",
            "Host: " . $urlDetails['host'],
            'Connection: Keep-Alive',
        );

        $cURLHandle = curl_init();
        curl_setopt( $cURLHandle, CURLOPT_URL, $url );
        curl_setopt( $cURLHandle, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $cURLHandle, CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $cURLHandle, CURLOPT_HEADER, false );
        curl_setopt( $cURLHandle, CURLOPT_SSL_VERIFYPEER, false );

        $response = curl_exec( $cURLHandle );

        $curlErrno = curl_errno( $cURLHandle );
        if ( $curlErrno ) {
            $curlError = curl_error( $cURLHandle );
            throw new Exception( $curlError );
        }

        $captcha_response = json_decode( $response );

        return $captcha_response->success;
    }
}
