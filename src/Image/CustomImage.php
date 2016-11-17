<?php

namespace AlexaCRM\WordpressCRM\Image;

use AlexaCRM\WordpressCRM\Image;
use AlexaCRM\WordpressCRM\Plugin;
use Exception;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class CustomImage extends Image {

    public function __construct() {

        add_action( 'wp_ajax_msdyncrm_custom_image', array( &$this, 'imageUrl' ) );
        add_action( 'wp_ajax_nopriv_msdyncrm_custom_image', array( &$this, 'imageUrl' ) );

        $this->options = get_option( Plugin::PREFIX . "attachments" );
    }

    public function imageUrl() {

        $id = ( isset( $_GET["id"] ) && $_GET["id"] ) ? $_GET["id"] : null;

        $json = ( isset( $_GET["json"] ) );

        if ( $id && self::checkOptions( $this->options ) ) {

            try {

                if ( !$json ) {
                    if ( !session_id() ) {
                        session_start();
                    }

                    $etag = md5( "custom" . $id );

                    self::cacheNotModified( $etag );
                }

                $entityLogicalName = $this->options["custom_entity"];
                $fields            = $this->options["fields"];

                $columnSet = array(
                    $fields["mimetype"],
                    $fields["documentbody"],
                );

                $attachment = ASDK()->retrieve( ASDK()->entity( $entityLogicalName, $id ), $columnSet );

                if ( $attachment && in_array( $attachment->$fields["mimetype"], self::$mime_types ) ) {

                    if ( $json ) {
                        $array["mimetype"]     = $attachment->$fields["mimetype"];
                        $array["documentbody"] = $attachment->$fields["documentbody"];
                        echo json_encode( $array );
                    } else {
                        self::setHeaders( $etag, $attachment->$fields["mimetype"] );
                        echo base64_decode( $attachment->$fields["documentbody"] );
                    }
                }
            } catch ( Exception $ex ) {
            }
        }
        die();
    }

}
