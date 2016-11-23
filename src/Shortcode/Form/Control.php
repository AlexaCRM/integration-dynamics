<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Form;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Implements form control.
 *
 * @package AlexaCRM\WordpressCRM\Shortcode\Form
 */
class Control {

    public $name;

    public $inputname;

    public $label;

    public $showlabel = true;

    public $labelPosition = "Left";

    public $labelAlignment = "Left";

    public $labelClass = "";

    public $type;

    public $recordName;

    public $classid;

    public $options;

    public $format;

    public $disabled = false;

    public $required = false;

    public $readonly;

    public $visible = true;

    public $error = "";

    public $value;

    public $lookupTypes;

    public $jsValidators = array();

    public function __construct( $_name ) {
        $this->name      = $_name;
        $this->inputname = "entity[" . $_name . "]";
    }

    public function fromEntity( $entity, $mode ) {

        $property = $entity->attributes[ $this->name ];

        if ( ( !$property->isValidForCreate && $mode == "create" ) ||
             ( !$property->isValidForUpdate && $mode == "edit" )
        ) {
            $this->disabled = true;
        }

        if ( $property->requiredLevel != 'None' &&
             $property->requiredLevel != 'Recommended'
        ) {
            $this->required = true;

            $this->jsValidators['required'] = [
                'value'   => true,
                'message' => sprintf( __( '%s is required', 'integration-dynamics' ), $this->label ),
            ];
        }

        $this->format = strtolower( $property->format );

        /*
         * JS validation rules according to https://jqueryvalidation.org/documentation/
         */

        if ( $property->type == "String" && strlen( $this->format ) > 0 ) {
            switch ( strtolower( $this->format ) ) {
                case "text":
                    break;
                case "email":
                    $this->jsValidators['email'] = [
                        'value' => true,
                        'message' => sprintf( __( '%s must be a valid email address', 'integration-dynamics' ), $this->label ),
                    ];
                    break;
                case "textarea":
                    break;
                case "url":
                    $this->jsValidators['url'] = [
                        'value' => true,
                        'message' => sprintf( __( '%s must be a valid URL', 'integration-dynamics' ), $this->label )
                    ];
                    break;
                case "ticker symbol":
                    break;
                case "phone":
                    break;
            }
        }
        if ( $property->maxLength ) {
            $message                            = _n_noop(
                '%1$s must be less than %2$d character',
                '%1$s must be less than %2$d characters',
                'integration-dynamics'
            );
            $this->jsValidators['maxlength'] = [
                'value'   => (int)$property->maxLength,
                'message' => sprintf( translate_nooped_plural( $message, $property->maxLength, 'integration-dynamics' ), $this->label, $property->maxLength ),
            ];
        }
    }
}
