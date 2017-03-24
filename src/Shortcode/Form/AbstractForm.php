<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Form;

use AlexaCRM\WordpressCRM\Shortcode;
use AlexaCRM\WordpressCRM\Template;
use Exception;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

abstract class AbstractForm extends Shortcode {

    private static $modes = [ 'create', 'edit', 'readonly', 'upsert' ];

    protected static function parseShortcodeAttributes( $attributes ) {
        $attrs = shortcode_atts( [
            'entity' => null,
            'name' => null,
            'type' => null,

            'entity_name'                => null, // deprecated
            'form_name'                  => null, // deprecated
            'form_type'                  => null, // deprecated

            /**
             * Contains DOM selector to put errors and notices into.
             */
            'message_container'          => null,

            'mode'                       => null,
            'parameter_name'             => null,
            'captcha'                    => null,
            'message'                    => null,
            'hide_form'                  => null,
            'hide_values'                => true,
            'redirect_url'               => null,
            'default'                    => null,
            'email_to'                   => null,
            'attachment'                 => null,
            'attachment_label'           => null,
            'sourceid'                   => null,
            'lookuptypes'                => [ ],
            'lookupviews'                => [ ],
            'required'                   => null,
            'optional'                   => null,
            'ajax'                       => false,
            'enable_layout'              => false,
            'validation_error'           => '',
            'submit_error'               => '',
            'disable_default_for_create' => false,
            'disable_default_for_edit'   => true,
            'default_mode'               => [ ],
        ], $attributes );

        if ( is_null( $attrs['entity'] ) && !is_null( $attrs['entity_name'] ) ) {
            $attrs['entity'] = $attrs['entity_name'];
        }

        if ( is_null( $attrs['name'] ) && !is_null( $attrs['form_name'] ) ) {
            $attrs['name'] = $attrs['form_name'];
        }

        if ( is_null( $attrs['type'] ) && !is_null( $attrs['form_type'] ) ) {
            $attrs['type'] = $attrs['form_type'];
        }

        unset( $attrs['entity_name'] );
        unset( $attrs['form_name'] );
        unset( $attrs['form_type'] );

        /* Check required shortcode attributes */
        self::checkRequiredAttributes( $attrs );
        /* Validate attributes */
        self::validateAttributes( $attrs );

        return $attrs;
    }

    protected static function validateAttributes( $attributes ) {
        /* Validate mode parameter */
        if ( !in_array( strtolower( $attributes['mode'] ), self::$modes ) ) {
            throw new Exception( "Unknown parameter mode('" . $attributes['mode'] . "') specified in shortcode attributes" );
        }
    }

    protected static function checkRequiredAttributes( $attributes ) {
        /* Check required shortcode parameters */
        if ( $attributes["entity"] == null ) {
            throw new Exception( "entity attribute is required, please provide entity attibute" );
        }

        if ( $attributes["name"] == null ) {
            throw new Exception( "name attribute is required, please provide name attibute" );
        }

        if ( $attributes["mode"] == null ) {
            throw new Exception( "mode shortcode attribute is required, please provide mode attibute" );
        }

        if ( strtolower( $attributes['mode'] ) != "create" && $attributes["parameter_name"] == null ) {
            throw new Exception( "parameter_name shortcode attribute is required, please provide parameter_name attibute" );
        }
    }

    protected static function parseCaptchaAttribute( $captchaAttribute ) {
        return ( $captchaAttribute == "true" ) ? true : false;
    }

    protected static function parseAttachmentAttribute( $attachmentAttribute ) {
        $attachment = null;

        if ( $attachmentAttribute && $attachmentAttribute === "true" ) {
            $attachment = true;
        } elseif ( $attachmentAttribute && $attachmentAttribute == "false" ) {
            $attachment = false;
        }

        return $attachment;
    }

    protected static function parseQueryString() {
        $queryString = ACRM()->request->getQueryString();

        $result = [];
        foreach ( explode( '&', $queryString ) as $argumentPairString ) {
            list( $argName, $argValue ) = explode( '=', $argumentPairString );

            if ( !$argName ) {
                continue;
            }

            if ( !array_key_exists( $argName, $result ) ) {
                $result[$argName] = [];
            }

            if ( $argValue ) {
                array_push( $result[$argName], $argValue );
            }
        }

        foreach ( $result as $key => $value ) {
            $result[ $key ] = implode( ", ", $value );
        }

        return $result;
    }

    protected static function parseModeAttribute( $mode, $parameterName = null ) {
        $mode = strtolower( $mode );
        if ( $mode == 'upsert' && $parameterName && ACRM()->request->query->has( $parameterName ) ) {
            return 'edit';
        } else if ( $mode == 'upsert' ) {
            return 'create';
        }

        return $mode;
    }

    protected static function parseFieldPropertiesAttributes( $attribute ) {
        $result = array();

        if ( substr_count( $attribute, "," ) > 0 ) {
            foreach ( explode( ',', $attribute ) as $attr ) {
                array_push( $result, trim( $attr ) );
            }
        } else {
            array_push( $result, trim( $attribute ) );
        }

        return $result;
    }

    protected static function parseDefaultAttribute( $defaultValues ) {
        $default = null;
        /* Check default fields */
        if ( $defaultValues ) {
            /* Remove enclosing braces */
            $array = array_filter( preg_split( "/[{}]/", $defaultValues ) );
            /* Extract field name and default values */
            foreach ( $array as $arr ) {
                /* if () */

                $temp = explode( ":", $arr );

                $default[ $temp[0] ] = $temp[1];
            }
            /* Remove empty field names */
            if ( isset( $default[""] ) ) {
                unset( $default[""] );
            }
            /* Remove empty default values */
            $default = array_filter( $default );
            /* Set variable to NULL if array is empty */
            if ( empty( $default ) ) {
                $default = null;
            }
        }

        return $default;
    }

    protected static function parseLookupTypesAttribute( $lookupTypes ) {
        $types = array();
        /* Check default fields */
        if ( $lookupTypes ) {
            /* Remove enclosing braces */
            $array = array_filter( preg_split( "/[{}]/", $lookupTypes ) );
            /* Extract field name and default values */
            foreach ( $array as $arr ) {
                $temp              = explode( ":", $arr );
                $types[ $temp[0] ] = explode( ",", trim( $temp[1] ) );
            }
            /* Remove empty field names */
            if ( isset( $types[""] ) ) {
                unset( $types[""] );
            }
            /* Remove empty default values */
            $types = array_filter( $types );
            /* Set variable to NULL if array is empty */
            if ( empty( $types ) ) {
                $types = array();
            }
        }

        return $types;
    }

    public static function parseParameterName( $parameterName, $mode ) {
        if ( strtolower( $mode ) == "create" ) {
            return null;
        }

        $parameterValue = ACRM()->request->query->get( $parameterName );
        if ( $parameterValue !== null ) {
            /* TODO function thar filters entity by attribute that NOT AN ID */
            return str_replace( "}", "", ( str_replace( "{", "", $parameterValue ) ) );
        }

        /**
         * Allows adding support for custom variables in "parameter_name" shortcode argument.
         * Must return null if the expected value is not found in $parameterName
         *
         * @param null|mixed $result
         * @param string $parameterName Parameter value to check
         */
        $result = apply_filters( 'wordpresscrm_form_parse_parameter_name', null, $parameterName );

        return $result;
    }

    public static function setupRequiredControlsFromAttributes( $columns, $requiredFields ) {
        foreach ( $columns as $k => $column ) {
            foreach ( $requiredFields as $field ) {
                if ( isset( $columns[ $k ]["controls"][ $field ] ) ) {
                    $control = $columns[ $k ]["controls"][ $field ];

                    $control->required = true;
                    $control->jsValidators['required'] = [
                        'value'   => true,
                        'message' => sprintf( __( '%s is required', 'integration-dynamics' ), $control->label ),
                    ];
                }
            }
        }

        return $columns;
    }

    public static function setupOptionalControlsFromAttributes( $columns, $optionalFields ) {
        foreach ( $columns as $k => $column ) {
            foreach ( $optionalFields as $field ) {
                if ( !empty( $columns[ $k ]["controls"][ $field ] ) ) {
                    $columns[ $k ]["controls"][ $field ]->required = false;

                    if ( isset( $columns[ $k ]["controls"][ $field ]->jsValidators['required'] ) ) {
                        unset( $columns[ $k ]["controls"][ $field ]->jsValidators['required'] );
                    }
                }
            }
        }

        return $columns;
    }

    public static function setValuesToControls( &$columns, &$entity ) {
        foreach ( $columns as $k => $column ) {
            foreach ( $column["controls"] as $control ) {
                $name = $control->name;

                if ( isset( $entity->{$name} ) ) {
                    if ( $entity->attributes[ $name ]->isLookup ) {
                        $control->value      = ( isset( $entity->{$name} ) && $entity->{$name} ) ? $entity->{$name}->ID : null;
                        $control->recordName = ( isset( $entity->{$name}->displayname ) && $entity->{$name}->displayname ) ? $entity->{$name}->displayname : null;
                    } else {
                        $control->value = ( isset( $entity->{$name} ) ) ? $entity->{$name} : null;
                    }
                }
            }
        }

        return $columns;
    }

    public static function printFormErrors( $errors ) {
        return ACRM()->getTemplate()->printTemplate( 'form/messages/errors.php', array( 'errors' => $errors ) );
    }

}
