<?php

// Exit if accessed directly
namespace AlexaCRM\WordpressCRM\Shortcode;

use AlexaCRM\WordpressCRM\Shortcode;
use Exception;
use AlexaCRM\WordpressCRM\Image\AnnotationImage;
use AlexaCRM\WordpressCRM\DataBinding;

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
     * @param $name
     *
     * @return mixed
     */
    public function __get( $name ) {
        return DataBinding::instance()->{$name};
    }

    /**
     * Shortcode handler
     *
     * @param array $atts
     * @param string $content
     * @param string $tagName
     *
     * @return string
     */
    public function shortcode( $atts, $content = null, $tagName ) {

        try {

            if ( $this->entity == null ) {
                return '';
            }

            $a = shortcode_atts( array(
                'field'    => null,
                'format'   => null,
                'locale'   => null,
                'add_form' => null,
                'nowrap'   => false,
            ), $atts );

            $format = null;

            $nowrap = $a["nowrap"];

            if ( isset( $a["format"] ) ) {

                $format = $a["format"];
            }

            if ( isset( $a["locale"] ) ) {
                setlocale( LC_MONETARY, $a["locale"] );
            }

            $timezoneOffset = null;

            if ( isset( $_SESSION["bearer"] ) ) {

                $timezoneOffset = ( isset( $_SESSION["bearer"]["timezonebias"] ) ) ? $_SESSION["bearer"]["timezonebias"] : null;
            }

            if ( isset( $_SESSION["alexaWPSDK"] ) ) {

                $timezoneOffset = ( isset( $_SESSION["alexaWPSDK"]["timezoneoffset"] ) ) ? $_SESSION["alexaWPSDK"]["timezoneoffset"] : null;
            }

            if ( $a['field'] == 'notes' ) {

                return $this->printNotes( $a['add_form'] == true );
            } else if ( in_array( $a['field'], array( 'attachedimage', 'attachmentimage' ) ) ) {

                return self::wrap( AnnotationImage::getFieldAttachmentImage( $this->entity ), $nowrap );
            } else if ( $a['field'] == "entityimage" ) {

                $fetch = "<fetch mapping='logical'>
                                <entity name='" . $this->entity->logicalname . "'>
                                        <attribute name='entityimage' />
                                        <filter type='and'>
                                                <condition attribute='" . $this->entity->getPrimaryIdField() . "' operator='eq' value='" . $this->entity->id . "' />
                                        </filter>
                                </entity>
                          </fetch>";

                $entityImage = ASDK()->retrieveSingle( $fetch );

                if ( $entityImage && $entityImage->entityimage ) {

                    $image = "<img src='data:image;base64," . $entityImage->entityimage . "'>";

                    return self::wrap( $image, $nowrap );
                }
            } else {
                if ( strpos( $a['field'], '.' ) ) {
                    $arr   = explode( '.', $a['field'] );
                    $field = $arr[0];
                    $child = strtolower( $arr[1] );
                } else {
                    $field = $a['field'];
                    $child = null;
                }

                if ( isset( $field ) && isset( $this->entity->{$field} ) && $this->entity->{$field} != null ) {

                    if ( $child && ( $child == "id" || $child == "logicalname" || $child == "displayname" ) ) {

                        $entity = $this->entity->{$field};

                        $field = $child;
                    } else if ( $child ) {

                        $entity = ASDK()->entity( $this->entity->{$field}->logicalname, $this->entity->{$field}->id );

                        $field = $child;
                    } else {
                        $entity = $this->entity;
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
                return apply_filters( "wordpresscrm_field_wrapper", "<p>" . $value . "</p>" );
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

    /**
     * @param string $entityLogicalName
     *
     * @return array
     */
    public static function getDataBindPage( $entityLogicalName ) {

        $args  = array(
            'post_type'  => array( 'page', 'post' ),
            'meta_query' => array(
                array(
                    'key'   => '_wordpresscrm_databinding_entity',
                    'value' => $entityLogicalName
                ),
                array(
                    'key'   => '_wordpresscrm_databinding_isdefaultview',
                    'value' => 'true'
                )
            )
        );
        $posts = get_posts( $args );

        return $posts;
    }

}
