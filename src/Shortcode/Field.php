<?php

// Exit if accessed directly
namespace AlexaCRM\WordpressCRM\Shortcode;

use AlexaCRM\WordpressCRM\Shortcode;
use Exception;
use AlexaCRM\WordpressCRM\Image\AnnotationImage;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Field shortcode [msdyncrm_field]
 *
 * @property \AlexaCRM\CRMToolkit\Entity $entity
 */
class Field extends Shortcode {

    /**
     * Errors collection
     *
     * @var array
     */
    private $errors = [ ];

    /**
     * Shortcode handler
     *
     * @param array $attributes
     * @param string $content
     * @param string $tagName
     *
     * @return string
     */
    public function shortcode( $attributes, $content = null, $tagName ) {

        try {
            $record = ACRM()->getBinding()->getEntity();

            if ( $record == null ) {
                return '';
            }

            $a = shortcode_atts( array(
                'field'    => null,
                'format'   => null,
                'locale'   => null,
                'add_form' => null,
                'nowrap'   => true,
            ), $attributes );

            $format = null;

            $nowrap = ( $a["nowrap"] === true || trim( $a['nowrap'] ) === 'true' );

            if ( isset( $a["format"] ) ) {

                $format = $a["format"];
            }

            if ( isset( $a["locale"] ) ) {
                setlocale( LC_MONETARY, $a["locale"] );
            }

            $timezoneOffset = null;

            if ( $a['field'] == 'notes' ) {
                return $this->printNotes( $a['add_form'] == true );
            }

            if ( in_array( $a['field'], array( 'attachedimage', 'attachmentimage' ) ) ) {
                return self::wrap( AnnotationImage::getFieldAttachmentImage( $record ), $nowrap );
            }

            if ( $a['field'] == "entityimage" ) {

                $fetch = "<fetch mapping='logical'>
                                <entity name='" . $record->logicalname . "'>
                                        <attribute name='entityimage' />
                                        <filter type='and'>
                                                <condition attribute='" . $record->getPrimaryIdField() . "' operator='eq' value='" . $record->id . "' />
                                        </filter>
                                </entity>
                          </fetch>";

                $entityImage = ASDK()->retrieveSingle( $fetch );

                if ( $entityImage && $entityImage->entityimage ) {

                    $image = "<img src='data:image;base64," . $entityImage->entityimage . "'>";

                    return self::wrap( $image, $nowrap );
                }

                return '';
            }

            if ( strpos( $a['field'], '.' ) ) {
                $arr   = explode( '.', $a['field'] );
                $field = $arr[0];
                $child = strtolower( $arr[1] );
            } else {
                $field = $a['field'];
                $child = null;
            }

            if ( isset( $field ) && isset( $record->{$field} ) && $record->{$field} != null ) {

                if ( $child ) {
                    $entity = $record->{$field};
                    $field = $child;
                } else {
                    $entity = $record;
                }

                try {

                    if ( $format != null && $entity->attributes[ $field ]->type == "DateTime" ) {
                        return self::wrap( date( $format, $entity->{$field} ), $nowrap );
                    } else if ( $format != null && $entity->attributes[ $field ]->type == "Double" ) {
                        $fl_format = str_split( $format );

                        if ( !isset( $fl_format[2] ) ) {
                            $fl_format[2] = ",";
                        }

                        if ( !isset( $fl_format[1] ) ) {
                            $fl_format[1] = ".";
                        }

                        if ( !isset( $fl_format[0] ) ) {
                            $fl_format[0] = 0;
                        }

                        return self::wrap( number_format( (float) $entity->{$field}, $fl_format[0], $fl_format[1], $fl_format[2] ), $nowrap );
                    } else if ( $format != null && $entity->attributes[ $field ]->type == "Money" ) {

                        return self::wrap( money_format( $format, $entity->{$field} ), $nowrap );
                    } else {

                        $value = $entity->getFormattedValue( $field, $timezoneOffset );

                        if ( isset( $entity->attributes[ $field ] ) ) {
                            switch ( $entity->attributes[ $field ]->format ) {
                                case "Email":
                                    $value = "<a href='mailto:" . $value . "'>" . $value . "</a>";
                                    break;
                                case "Url":
                                    $value = "<a href='" . $value . "'>" . $value . "</a>";
                                    break;
                                case "Phone":
                                    $value = "<a href='tel:" . $value . "'>" . $value . "</a>";
                                    break;
                            }
                        }

                        return self::wrap( $value, $nowrap );
                    }
                } catch ( Exception $ex ) {
                    return $ex->getMessage();
                }
            } else {
                return apply_filters( "wordpresscrm_field_missing", "", $a['field'] );
            }
        } catch ( Exception $ex ) {
            return self::returnExceptionError( $ex );
        }
    }

    /**
     * @param $value
     * @param $nowrap
     *
     * @return mixed|string
     */
    public static function wrap( $value, $nowrap ) {

        if ( $value ) {
            if ( $nowrap ) {
                return $value;
            } else {
                return apply_filters( "wordpresscrm_field_wrapper", "<p>" . $value . "</p>", $value );
            }
        }

        return "";
    }

    /**
     * @param bool $includeNotesForm
     *
     * @return string
     */
    public function printNotes( $includeNotesForm = false ) {
        if ( $this->entity != null && $this->entity->ID ) {

            $fetchXML = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
                                <entity name="annotation">
                                  <all-attributes  />
                                  <filter type="and">
                                        <condition attribute="objectid" operator="eq" value="' . $this->entity->ID . '" />
                                  </filter>
                                </entity>
                          </fetch>';

            $annotaions = ASDK()->retrieveMultiple( $fetchXML );
        } else {
            $annotaions = null;
        }

        $output = "";

        if ( $annotaions && $annotaions->Count > 0 ) {

            $output .= '<section id="comments">';

            $output .= '<ol class="media-list">';

            foreach ( $annotaions->Entities as $annotation ) {

                $output .= $this->printNote( $annotation );
            }

            $output .= '</ol>';

            $output .= '</section>';
        }

        return $output;
    }

    /**
     * @param $annotation
     *
     * @return string
     */
    public function printNote( $annotation ) {

        $output = "";

        $name = ( isset( $annotation->subject ) && $annotation->subject ) ? $annotation->subject : $annotation->createdby->displayname;

        $output .= '<li class="comment even thread-eve media">';
        $output .= '<img width="56" height="56" class="avatar pull-left media-object avatar-56 photo avatar-default" srcset="http://2.gravatar.com/avatar/?s=112&amp;d=mm&amp;r=g 2x" src="http://1.gravatar.com/avatar/?s=56&amp;d=mm&amp;r=g" alt="">';
        $output .= '<div class="media-body">';
        $output .= '<div class="comment-header clearfix">';
        $output .= '<h5 class="media-heading">' . $name . '</h5>';
        $output .= '<div class="comment-meta">';
        $output .= '<time datetime="' . date( "c", $annotation->createdon ) . '">' . $annotation->getFormattedValue( "createdon" ) . '</time>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= "<p>" . $annotation->notetext . "</p>";
        $output .= '</div>';
        $output .= '</li>';

        return $output;
    }

}
