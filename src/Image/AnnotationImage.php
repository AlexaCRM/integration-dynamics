<?php

namespace AlexaCRM\WordpressCRM\Image;

use AlexaCRM\CRMToolkit\AbstractClient;
use AlexaCRM\WordpressCRM\Image;
use AlexaCRM\WordpressCRM\PersistentStorage;
use Exception;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Serves images stored as attachments in CRM annotations
 *
 * @package AlexaCRM\WordpressCRM\Image
 */
class AnnotationImage extends Image {

    /**
     * @var PersistentStorage
     */
    protected $storage;

    /**
     * AnnotationImage constructor.
     *
     * @param PersistentStorage $storage
     */
    public function __construct( PersistentStorage $storage ) {
        $this->storage = $storage;

        add_action( 'wp_ajax_msdyncrm_image', [ $this, 'serveImage' ] );
        add_action( 'wp_ajax_nopriv_msdyncrm_image', [ $this, 'serveImage' ] );
    }

    /**
     * Serves requested image to the user-agent
     */
    public function serveImage() {
        $query = ACRM()->request->query;

        $isJsonExpected = $query->has( 'json' );

        if ( !$query->has( 'id' ) ) {
            $this->sendError( 'Query parameter "id" is required.', 400, $isJsonExpected );
        }

        $imageId = $query->get( 'id' );
        if ( !AbstractClient::isGuid( $imageId ) ) {
            $this->sendError( 'Query parameter "id" must be a GUID.', 400, $isJsonExpected );
        }

        $imageWidth = $query->getInt( 'width' );

        $imageCode = sha1( $imageId . ( ( $imageWidth > 0 )? $imageWidth : '' ) );

        if ( $this->storage->exists( $imageCode ) ) {
            $this->sendResponse( $imageCode, $this->storage->get( $imageCode ), $isJsonExpected );
        }

        try {
            $image = $this->retrieveImage( $imageId, $isJsonExpected );

            if ( $imageWidth > 0 ) {
                $image['documentbody'] = $this->resizeImage( $image['documentbody'], $imageWidth );
            }

            $this->storage->set( $imageCode, $image );

            $this->sendResponse( $imageCode, $image, $isJsonExpected );
        } catch ( Exception $e ) {
            $this->sendError( $e->getMessage(), 404, $isJsonExpected );
        }
    }

    /**
     * Retrieves an image from CRM
     *
     * (mimetype => string, documentbody => base64string)
     *
     * @param string $id Annotation ID
     * @param bool $isJsonExpected
     *
     * @return array
     */
    protected function retrieveImage( $id, $isJsonExpected = false ) {
        $imageCode = sha1( $id );
        if ( $this->storage->exists( $imageCode ) ) {
            return $this->storage->get( $imageCode );
        }

        $columnSet = [ 'mimetype', 'documentbody', 'annotationid' ];
        $annotation = ASDK()->entity( 'annotation', $id, $columnSet );

        if ( !$annotation->exists ) {
            $this->sendError( 'Image not found.', 404, $isJsonExpected );
        }

        if ( !in_array( $annotation->mimetype, static::$mime_types ) ) {
            $this->sendError( 'Image type is not supported.', 400, $isJsonExpected );
        }

        $image = [
            'mimetype' => $annotation->mimetype,
            'documentbody' => $annotation->documentbody,
        ];

        $this->storage->set( $imageCode, $image );

        return $image;
    }

    /**
     * Resizes the image
     *
     * @param string $imageContent Base64 encoded image
     * @param int $newWidth New image width
     *
     * @return string Base64 encoded image
     */
    protected function resizeImage( $imageContent, $newWidth = 0 ) {
        if ( !$newWidth || !function_exists( 'getimagesizefromstring' ) ) { // restricts to >=PHP5.4
            return $imageContent;
        }

        $originalImageString = base64_decode( $imageContent );
        $originalSize = getimagesizefromstring( $originalImageString );
        if ( $originalSize === false ) {
            return $imageContent;
        }

        $originalImage = imagecreatefromstring( $originalImageString );

        $newSize = wp_constrain_dimensions( $originalSize[0], $originalSize[1], $newWidth );
        $newImage = imagecreatetruecolor( $newSize[0], $newSize[1] );
        imagecopyresampled( $newImage, $originalImage, 0, 0, 0, 0, $newSize[0], $newSize[1], $originalSize[0], $originalSize[1] );

        ob_start();
        imagejpeg( $newImage, null, 85 );
        $newImageString = ob_get_flush();

        return base64_encode( $newImageString );
    }

    /**
     * Renders and error and halts execution
     *
     * @param string $message
     * @param int $httpCode
     * @param bool $isJsonExpected
     */
    protected function sendError( $message, $httpCode = 400, $isJsonExpected = false ) {
        if ( $isJsonExpected ) {
            wp_send_json_error( [ 'message' => $message ] );
        }

        http_response_code( $httpCode );
        wp_die( $message );
    }

    /**
     * Renders the response and halts execution
     *
     * @param array $imageCode Image in an AnnotationImage::retrieveImage()-like format
     * @param array $data
     * @param bool $isJsonExpected
     */
    protected function sendResponse( $imageCode, array $data, $isJsonExpected = false ) {
        if ( $isJsonExpected ) {
            wp_send_json( $data );
        }

        static::cacheNotModified( $imageCode );
        static::setHeaders( $imageCode, $data['mimetype'] );
        echo base64_decode( $data['documentbody'] );

        wp_die();
    }

}
