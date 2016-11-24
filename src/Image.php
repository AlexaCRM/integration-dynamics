<?php

namespace AlexaCRM\WordpressCRM;

use Exception;
use SimpleXMLElement;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Image {

    protected $options = null;

    public static $cacheExpiryTime = 86400;

    public static $mime_types = array(
        "image/gif",
        "image/jpeg",
        "image/pjpeg",
        "image/png",
        "image/svg+xml",
        "image/tiff",
        "image/vnd.microsoft.icon",
        "image/vnd.wap.wbmp",
    );

    protected static function cacheNotModified( $etag ) {
        $request = ACRM()->request->server;

        $ifModifiedSince = $request->get( 'HTTP_IF_MODIFIED_SINCE' );
        $ifNoneMatch = $request->get( 'HTTP_IF_NONE_MATCH' );

        if ( !$ifModifiedSince || !$ifNoneMatch ) {
            return;
        }

        $minHittingTime = time() - self::$cacheExpiryTime;
        $requestEtag = str_replace( '"', '', stripslashes( $ifNoneMatch ) );

        if ( ( strtotime( $ifModifiedSince ) > $minHittingTime ) && $requestEtag === $etag ) {
            header( "Cache-Control: public, max-age=86400, pre-check=86400" );
            header( "Pragma: private" );
            header( 'HTTP/1.1 304 Not Modified' );
            exit();
        }
    }

    protected static function setHeaders( $etag, $mimetype ) {
        header( 'ETag: "' . $etag . '"' );
        header( "Cache-Control: public, max-age=10800, pre-check=10800" );
        header( "Pragma: private" );
        header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', time() ) . ' GMT' );
        header( "Expires: " . gmdate( 'D, d M Y H:i:s', ( time() + self::$cacheExpiryTime ) ) . ' GMT' );
        header( "Content-type: " . $mimetype );
    }

    protected static function checkImageMimetype( $mimetype ) {

        return in_array( $mimetype, self::$mime_types );
    }

    protected static function checkOptions( $options = null ) {
        return ( $options && isset( $options["custom_entity"] ) && $options["custom_entity"] && isset( $options["fields"] ) && $options["fields"] && isset( $options["fields"]["objectid"] ) && $options["fields"]["objectid"] && isset( $options["fields"]["objecttype"] ) && $options["fields"]["objecttype"] && isset( $options["fields"]["mimetype"] ) && $options["fields"]["mimetype"] && isset( $options["fields"]["documentbody"] ) && $options["fields"]["documentbody"] );
    }

    public static function getFieldAttachmentImage( $entity = null ) {

        $customEntity = false;

        if ( $entity != null && $entity->ID ) {

            $options = get_option( Plugin::PREFIX . "attachments" );

            $customEntity = ( $options && self::checkOptions( $options ) );

            $annotationsUseView = ( $options && isset( $options["annotations_use_view"] ) && $options["annotations_use_view"] && $options["annotations_view_name"] );

            $fetchXML = ( $customEntity ) ? self::constructFetchForCustomImage( $options, $entity ) : null;

            if ( !$fetchXML && $annotationsUseView ) {
                $fetchXML = self::constructFetchForAnnotationView( $options, $entity );
            }

            if ( !$fetchXML ) {
                $fetchXML = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false" count="1">
                                    <entity name="annotation">
                                      <attribute name="mimetype" />
                                      <attribute name="documentbody" />
                                      <attribute name="annotationid" />
                                      <filter type="and">
                                            <condition attribute="objectid" operator="eq" value="' . $entity->ID . '" />
                                      </filter>
                                    </entity>
                              </fetch>';
            }

            try {
                $annotaion = ASDK()->retrieveSingle( $fetchXML );
            } catch ( Exception $ex ) {
                $annotaion = null;
            }
        } else {
            $annotaion = null;
        }

        $mimetype     = ( $customEntity ) ? $options['fields']['mimetype'] : "mimetype";
        $documentbody = ( $customEntity ) ? $options['fields']['documentbody'] : "documentbody";

        if ( $annotaion && self::checkImageMimetype( $annotaion->$mimetype ) ) {
            return "<img src='data:" . $annotaion->$mimetype . ";base64," . $annotaion->$documentbody . "' />";
        }
    }

    public static function constructFetchForAnnotationView( $options, $entity ) {

        $view = View::getViewForEntity( "annotation", $options["annotations_view_name"] );
        if ( !$view ) {
            return null;
        }
        $query              = new SimpleXMLElement( $view->fetchxml );
        $entityXMLElement   = $query->xpath( './/entity' );
        $mimetypeXMLElement = $query->xpath( './/attribute[name="mimetype"]' );
        if ( empty( $mimetypeXMLElement ) ) {
            $attribute = $entityXMLElement[0]->addChild( "attribute" );
            $attribute->addAttribute( "name", "mimetype" );
        }
        $documentbodyXMLElement = $query->xpath( './/attribute[name="documentbody"]' );
        if ( empty( $documentbodyXMLElement ) ) {
            $attribute = $entityXMLElement[0]->addChild( "attribute" );
            $attribute->addAttribute( "name", "documentbody" );
        }
        $condition = $query->xpath( './/condition[@attribute="objectid"]' );

        if ( count( $condition ) > 0 ) {
            $newCondition = $condition;
            unset( $newCondition[0]["uiname"] );
            unset( $newCondition[0]["uitype"] );
            $newCondition[0]["value"] = "{" . $entity->id . "}";

            return str_replace( $condition[0]->asXML(), $newCondition[0]->asXML(), $query->asXML() );
        } else {
            $filterXMLElement = $entityXMLElement[0]->addChild( "filter" );
            $filterXMLElement->addAttribute( "type", "and" );
            $conditionXMLElement = $filterXMLElement[0]->addChild( "condition" );
            $conditionXMLElement->addAttribute( "value", "{" . $entity->id . "}" );
            $conditionXMLElement->addAttribute( "operator", "eq" );
            $conditionXMLElement->addAttribute( "attribute", "objectid" );

            return trim( str_replace( '<?xml version="1.0"?>', "", $query->asXML() ) );
        }
    }

    public static function constructFetchForCustomImage( $options, $entity ) {

        try {
            $defaultImageField = ( isset( $options["fields"]["default_image"] ) && $options["fields"]["default_image"] ) ? $options["fields"]["default_image"] : null;

            $fetchXML = '<fetch version="1.0" output-format="xml-platform" mapping="logical">
                            <entity name="' . $options["custom_entity"] . '">
                              <attribute name="' . $options["fields"]["mimetype"] . '" />
                              <attribute name="' . $options["fields"]["documentbody"] . '" />';
            if ( $defaultImageField ) {
                $fetchXML .= '<attribute name="' . $options["fields"]["default_image"] . '" />';
            }
            $fetchXML .= '
                            <filter type="and">
                                  <condition attribute="' . $options["fields"]["objectid"] . '" operator="eq" value="{' . $entity->ID . '}" />
                                  <condition attribute="' . $options["fields"]["objecttype"] . '" operator="eq" value="' . $entity->logicalname . '" />
                            </filter>';
            if ( $defaultImageField ) {
                //$fetchXML .= '<filter type="or">';
                //	$fetchXML .= '<condition attribute="'.$defaultImageField.'" operator="not-null" />';
                $fetchXML .= '<condition attribute="' . $defaultImageField . '" operator="eq" value="1" />';
                //$fetchXML .= '</filter>';
            }
            $fetchXML .= '
                            </entity>
                      </fetch>';

            return $fetchXML;
        } catch ( Exception $ex ) {
        }

        return null;
    }

}
