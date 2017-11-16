<?php

namespace AlexaCRM\WordpressCRM;

use AlexaCRM\CRMToolkit\Client;

/**
 * Endpoint for the forced CRM attachment downloads
 */
class AnnotationPusher {

    /**
     * AnnotationPusher constructor.
     */
    public function __construct() {
        add_action( 'wp_ajax_msdyncrm_attachment', [ $this, 'serveAnnotation' ] );
        add_action( 'wp_ajax_nopriv_msdyncrm_attachment', [ $this, 'serveAnnotation' ] );
    }

    /**
     * Serves the attachments by ID via forced-download.
     */
    public function serveAnnotation() {
        $sdk = ACRM()->getSdk();
        if ( !ACRM()->connected() || !$sdk ) {
            wp_die( 'Cannot retrieve the attachment at the moment. Try again later.' );
        }

        $query = ACRM()->request->query;

        if ( !$query->has( 'id' ) ) {
            wp_die( 'Query parameter "id" is required.' );
        }

        $attachmentId = $query->get( 'id' );
        if ( !Client::isGuid( $attachmentId ) ) {
            wp_die( 'Query parameter "id" must be a GUID.' );
        }

        $attachmentColumns = [ 'documentbody', 'mimetype', 'filename' ];
        $attachment = $sdk->entity( 'annotation', $attachmentId, $attachmentColumns );
        if ( !$attachment || !$attachment->exists ) {
            wp_die( 'Not found.', null, [ 'response' => 404 ] );
        }

        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/octet-stream' );

        header( 'Content-Disposition: attachment; filename=' . $attachment->filename );

        echo base64_decode( $attachment->documentbody );

        wp_die();
    }

}
