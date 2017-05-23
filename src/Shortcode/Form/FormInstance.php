<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Form;

use AlexaCRM\CRMToolkit\Entity;
use AlexaCRM\WordpressCRM\Template;
use AlexaCRM\WordpressCRM\Messages;
use AlexaCRM\WordpressCRM\FormValidator;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Implements form shortcode.
 *
 * @package AlexaCRM\WordpressCRM\Shortcode\Form
 */
class FormInstance extends AbstractForm {

    /**
     * @var GCaptcha
     */
    public $captcha = null;

    /**
     * @var array
     */
    private $errors = [ ];

    /**
     * @var array
     */
    private $notices = [ ];

    /**
     * Dynamics CRM form name.
     *
     * @var string
     */
    private $formName = null;

    /**
     * Dynamics CRM form type.
     *
     * @var int
     * @see https://msdn.microsoft.com/en-us/library/mt607589.aspx
     */
    private $formType = null;

    /**
     * Form mode. May be 'readonly', 'create', 'update', or 'edit'.
     *
     * @var string
     */
    private $mode;

    /**
     * Entity that the form belongs to.
     *
     * @var Entity
     */
    public $entity = null;

    /**
     * List of controls per form column.
     *
     * @var array
     */
    public $controls = [ ];

    private $attachment = false;

    private $attachmentLabel = "";

    /**
     * Definitions of default field values.
     *
     * @var array
     */
    private $default = [ ];

    /**
     * Form mode constraints for default values.
     *
     * @var array
     */
    private $defaultMode = [ ];

    private $lookupTypes = [ ];

    private $lookupViews = [ ];

    /**
     * List of required fields overrides.
     *
     * @var array
     */
    private $requiredFields = [ ];

    /**
     * List of optional fields overrides.
     *
     * @var array
     */
    private $optionalFields = [ ];

    private $disableDefaultForCreate = false;

    private $disableDefaultForEdit = false;

    /**
     * @var FormValidator
     */
    private $validator = null;

    /**
     * Form validation result after submitting the form.
     *
     * @var array
     */
    private $entityErrors = [ ];

    /**
     * Success message.
     *
     * @var string
     */
    private $successMessage = '';

    /**
     * FormXML definition of the form. Retrieved from Dynamics CRM.
     *
     * @var string
     */
    private $formXML = null;

    /**
     * Whether to honor FormXML layout preferences or not.
     *
     * @var bool
     */
    private $disableLayout = false;

    /**
     * Unique form ID.
     *
     * @var string
     */
    private $formUid;

    private $ajax = false;

    private $showForm = true;

    /**
     * FormInstance constructor.
     */
    public function __construct() {
        $this->captcha   = new GCaptcha();
        $this->validator = new FormValidator();

        $this->successMessage = __( '<strong>Success!</strong>', 'integration-dynamics' );
    }

    public function __get( $name ) {
        switch ( strtolower( $name ) ) {
            case "formname":
                return $this->formName;
            case "controls":
                return $this->controls;
            case "captcha":
                return $this->captcha;
            case "notices":
                return $this->notices;
            case "errors":
                return $this->errors;
            case "showform":
                return ( $this->showForm ) ? true : false;
            case "uid":
                return $this->formUid;
            case "ajax":
                return $this->ajax;
        }
    }

    private function getFormPostData( $ajax = false ) {
        $request = ACRM()->request;

        if ( !$request->isMethod( 'POST' ) ) {
            return null;
        }

        $postData = null;

        $formData = $request->request;

        $nonceActionName = 'wpcrm-form-' . $formData->get( 'form_name', '' );
        if ( !wp_verify_nonce( $formData->get( '_wpnonce', '' ), $nonceActionName ) ) {
            throw new Exception( __( 'Form submission couldn\'t pass security check. Please try again', 'integration-dynamics' ) );
        }

        if ( $formData->has( 'entity' ) &&
             ( $formData->get( 'entity_form_name', '' ) == $this->formName ) &&
             ( $formData->get( 'entity_form_entity', '' ) == $this->entity->logicalname )
        ) {
            $postData = $formData->all();
        }

        return $postData;
    }

    /**
     * @param array $attributes
     * @param bool $ajax
     * @param null $shortcodeName
     *
     * @return string
     */
    public function shortcode( $attributes, $ajax = false, $shortcodeName = null ) {
        /* Check CRM connection */
        if ( !ACRM()->connected() ) {
            return self::notConnected();
        }

        try {
            /* Parse shortcode attributes */
            $attributes = self::parseShortcodeAttributes( $attributes );

            /* Lowercase mode parameter */
            $this->mode = self::parseModeAttribute( $attributes["mode"], $attributes["parameter_name"] );

            /* Disable default values when in create mode or in creade mode for upsert */
            $this->disableDefaultForCreate = ( $attributes["disable_default_for_create"] == "false" || !$attributes["disable_default_for_create"] ) ? false : true;

            /* Disable default values when in edit mode or in edit mode for upsert */
            $this->disableDefaultForEdit = ( $attributes["disable_default_for_edit"] == "false" || !$attributes["disable_default_for_edit"] ) ? false : true;

            /* Get default values array Entity form field as key, value definition as value ("value", "currentuser", "currentuser.field") */
            $this->default = self::parseDefaultAttribute( $attributes["default"] );

            /* Parse the mode attributes for default values */
            $this->defaultMode = self::parseKeyArrayShortcodeAttribute( $attributes["default_mode"] );

            /* Get ajax shortcode attribute */
            $this->ajax = $attributes["ajax"];

            /* Restrict entity types for lookup fields */
            $this->lookupTypes = self::parseLookupTypesAttribute( $attributes["lookuptypes"] );

            /* Set custom lookup views for fields */
            $this->lookupViews = self::parseLookupTypesAttribute( $attributes["lookupviews"] );

            /* Parse required entity fields */
            $this->requiredFields = self::parseFieldPropertiesAttributes( $attributes["required"] );

            /* Parse optional entity fields */
            $this->optionalFields = self::parseFieldPropertiesAttributes( $attributes["optional"] );

            /* Detect need to use attachments */
            $this->attachment = self::parseAttachmentAttribute( $attributes["attachment"] );

            /* Use captcha */
            $captcha = self::parseCaptchaAttribute( $attributes["captcha"] );

            $this->formName = strtolower( $attributes["name"] );
            $this->formType = $attributes["type"];

            /* Generate form unique ID for fronend validation */
            $this->formUid = uniqid( "entity-form-" );

            $entityName  = strtolower( $attributes["entity"] );
            $redirectUrl = $attributes["redirect_url"];

            if ( $attributes['message'] ) {
                $this->successMessage = $attributes['message'];
            }

            /* Check hide_form attribute exists and it value equals to "true" */
            $hideForm = ( $attributes["hide_form"] && $attributes["hide_form"] == "true" );

            /* Parse attachment label attribute for notescontrol form control label */
            $this->attachmentLabel = $attributes["attachment_label"];

            $this->disableLayout = ( $attributes["enable_layout"] != "true" );

            /* Retrieve parameter name */
            $id = self::parseParameterName( $attributes["parameter_name"], $this->mode );

            $this->entity = ASDK()->entity( $entityName ); // An empty entity record doesn't have performance penalty.

            // allow $id to be an entity record
            if ( $id instanceof Entity ) {
                $this->entity = $id;
            } elseif ( $id ) {
                $this->entity = ASDK()->entity( $entityName, $id );
            }

            /* Check that the entity or entity record exists */
            if ( !$this->entity ) {
                array_push( $this->errors, "Entity " . $entityName . " doesn't exist" );

                return self::printFormErrors( $this->errors );
            }

            /* Get the entity form xml */
            $this->formXML = $this->getFormXML( $this->formName );

            /* Retrieve form controls */
            if ( $this->formXML ) {
                $this->controls = $this->setupControls( $this->formXML );
            } else {
                array_push( $this->errors, __( 'Can not get form definition', 'integration-dynamics' ) );

                return self::printFormErrors( $this->errors );
            }

            /* Set required attributes from shortcode attribute to form controls */
            if ( !empty( $this->requiredFields ) ) {
                $this->controls = self::setupRequiredControlsFromAttributes( $this->controls, $this->requiredFields );
            }

            /* Set optional attributes from shortcode attribute to form controls */
            if ( !empty( $this->optionalFields ) ) {
                $this->controls = self::setupOptionalControlsFromAttributes( $this->controls, $this->optionalFields );
            }

            /* Set default values to controls */
            if ( !empty( $this->default ) ) {
                $this->setupDefaultControlsAndValues();
            }

            /* Set values from entity record to controls */
            $this->controls = self::setValuesToControls( $this->controls, $this->entity );

            /* Enqueue javascript */
            self::enqueueFormScripts();

            if ( $this->mode == "readonly" ) {
                $captcha = null;
            } else {
                if ( $postData = $this->getFormPostData( $ajax ) ) {
                    $this->processForm( $postData, $this->controls );

                    if ( $this->mode == "edit" ) {
                        $this->controls = self::setValuesToControls( $this->controls, $this->entity );
                    }

                    if ( !empty( $this->entityErrors ) ) {
                        array_push( $this->errors, ( $attributes["validation_error"] ) ? $attributes["validation_error"] : Messages::getMessage( "form", "validation_error" ) );
                    }

                    if ( $this->captcha->enable_captcha && ( !$this->captcha->checkResponse() || !$this->captcha->checkCaptcha() ) ) {
                        array_push( $this->errors, Messages::getMessage( "form", "invalid_captcha" ) );
                    }

                    $this->errors = apply_filters( "wordpresscrm_form_errors", $this->errors );

                    if ( empty( $this->errors ) ) {
                        try {
                            if ( $this->mode == "edit" ) {
                                $result = ASDK()->update( $this->entity );
                            } else {
                                $result = ASDK()->create( $this->entity );
                            }

                            if ( !$result ) {
                                array_push( $this->errors, ( $attributes["submit_error"] ) ? $attributes["submit_error"] : Messages::getMessage( "form", "crm_error" ) );
                            } else {
                                $objectId = ( $this->mode == "edit" ) ? $this->entity->id : $result;
                                $this->processAttachments( $objectId );

                                if ( $redirectUrl ) {
                                    wordpresscrm_javascript_redirect( $redirectUrl );
                                }

                                array_push( $this->notices, $this->successMessage );

                                $this->showForm = !$hideForm;
                            }
                        } catch ( Exception $ex ) {
                            $message = $ex->getMessage();
                            if ( $ex->getPrevious() instanceof Exception ) {
                                $message .= ' - ' . $ex->getPrevious()->getMessage();
                            }

                            array_push( $this->errors, $message );
                        }
                    } else if ( $this->mode == "create" ) {
                        $this->controls = self::setValuesToControls( $this->controls, $this->entity );
                    }
                }
            }

            add_action( 'wordpresscrm_after_form_end', function( $form ) {
                $messageContainer = $form->attributes['message_container'];
                if ( is_null( $messageContainer ) ) {
                    return;
                }

                add_action( 'wp_footer', function() use ( $messageContainer ) {
                    ?><script>
                        ( function( $ ) {
                            $( '.form-notices,.form-errors' ).detach().appendTo( <?php echo json_encode( $messageContainer ); ?> );
                        } )( jQuery );
                    </script>

                    <?php
                } );
            } );

            return apply_filters( "wordpresscrm_form_print_form", $this->printForm( $captcha ) );
        } catch ( Exception $ex ) {
            return self::returnExceptionError( $ex );
        }
    }

    /**
     * Uploads received form attachments to the CRM.
     *
     * @param $entity
     */
    private function processAttachments( $entity ) {
        $files = ACRM()->request->files;

        if ( !array_key_exists( 'notescontrol', $files->get( 'entity', [] ) ) ) {
            return;
        }

        /**
         * @var UploadedFile $uploadedFile
         */
        $uploadedFile = $files->get( 'entity' )['notescontrol'];
        if ( !( $uploadedFile instanceof UploadedFile ) ) {
            return;
        }

        $fileName = $uploadedFile->getClientOriginalName();
        $filePath  = $uploadedFile->getRealPath();
        $fileType = $uploadedFile->getMimeType();

        $base64 = base64_encode( file_get_contents( $filePath ) );

        $newAnnotation = ASDK()->entity( 'annotation' );

        if ( $entity instanceof Entity ) {
            $newAnnotation->objectid = $entity;
        } else {
            $entityObject            = ASDK()->entity( $this->entity->logicalname );
            $entityObject->id        = $entity;
            $newAnnotation->objectid = $entityObject;
        }
        $newAnnotation->subject      = 'Attachment file ' . $fileName;
        $newAnnotation->documentbody = $base64;
        $newAnnotation->mimetype     = $fileType;
        $newAnnotation->filename     = $fileName;

        ASDK()->create( $newAnnotation );
    }

    /**
     * Sets up default values for respective controls.
     *
     * @return void
     */
    private function setupDefaultControlsAndValues() {
        if ( ( $this->disableDefaultForCreate && $this->mode === 'create' )
             || ( $this->disableDefaultForEdit && $this->mode === 'edit' )
        ) {
            return;
        }

        $counter = - 1;
        foreach ( $this->default as $attributeName => $attributeValue ) {
            $counter ++;

            if ( isset( $this->defaultMode[ $counter ] ) &&
                 strtolower( $this->defaultMode[ $counter ] ) !== 'upsert' &&
                 strtolower( $this->defaultMode[ $counter ] ) !== $this->mode
            ) {
                continue;
            }

            end( $this->controls );
            $last = key( $this->controls );
            reset( $this->controls );

            $k = null;

            foreach ( $this->controls as $controlKey => $control ) {
                if ( isset( $this->controls[ $controlKey ]["controls"][ $attributeName ] ) ) {
                    $k = $attributeName;
                }
            }

            if ( isset( $this->entity->{$attributeName} ) ) {
                try {
                    /* Check $_GET parameter for defaults */
                    if ( strpos( $attributeValue, 'querystring' ) === 0 && strpos( $attributeValue, '.' ) !== false ) {
                        $explode = explode( ".", $attributeValue );

                        $queryParams = $this->parseQueryString();

                        if ( isset( $queryParams[ $explode[1] ] ) && $queryParams[ $explode[1] ] ) {
                            $queryValue = $queryParams[ $explode[1] ];

                            $this->createControl( ( $k == null ), $last, $attributeName, $queryValue );
                        } else {
                            unset( $this->default[ $attributeName ] );
                        }
                    } elseif ( strpos( $attributeValue, '.' ) !== false ) {
                        /**
                         * Allows to add support for custom variables in the "default" shortcode argument with syntax
                         * {var.field} (dot-notation)
                         *
                         * @param bool $setupDefault Whether to setup default (handler must return false!)
                         * @param FormInstance $form Form instance
                         * @param string $attributeValue Variable name (e.g. for {var.field} $value == "var.field")
                         * @param string|null $k Associated form control name if one exists, null otherwise
                         * @param string $attributeName Field name to associate value with
                         * @param string $last
                         */
                        $setupDefault = apply_filters( 'wordpresscrm_form_setup_with_comma', true, $this, $attributeValue, $k, $attributeName, $last );
                        if ( $setupDefault ) {
                            $this->createControl( ( $k == null ), $last, $attributeName, $attributeValue );
                        }
                    } else {
                        /**
                         * Allows to add support for custom variables in the "default" shortcode argument with syntax
                         * {variable}
                         *
                         * @param bool $setupDefault Whether to setup default (handler must return false!)
                         * @param FormInstance $form Form instance
                         * @param string $attributeValue Variable name (e.g. for {var.field} $value == "var.field")
                         * @param string|null $k Associated form control name if one exists, null otherwise
                         * @param string $attributeName Field name to associate value with
                         * @param string $last
                         */
                        $setupDefault = apply_filters( 'wordpresscrm_form_setup_without_comma', true, $this, $attributeValue, $k, $attributeName, $last );

                        if ( $setupDefault ) {
                            $this->createControl( ( $k == null ), $last, $attributeName, $attributeValue );
                        }
                    }
                } catch ( Exception $ex ) {
                    array_push( $this->errors, $ex->getMessage() );

                    self::printFormErrors( $this->errors );
                }
            }
        }
    }

    /**
     * Creates a control with specified value.
     *
     * @param bool $createControl
     * @param $control
     * @param $attributeName
     * @param $value
     */
    private function createControl( $createControl = false, $control, $attributeName, $value ) {
        if ( $createControl ) {
            $newControl = new Control( $attributeName );
            $newControl->visible = false;
            $newControl->value = $value;

            $this->controls[ $control ]["controls"][ $attributeName ] = $newControl;
            $this->entity->{$attributeName} = $value;
        } else {
            /* Simple value string */
            $this->entity->{$attributeName} = $value;

            if ( $this->entity->attributes[ $attributeName ]->isLookup ) {
                foreach ( $this->entity->attributes[ $attributeName ]->lookupTypes as $entityType ) {
                    try {
                        $lookup = ASDK()->entity( $entityType, $value );
                    } catch ( Exception $ex ) {
                        continue;
                    }
                    $this->entity->{$attributeName} = $lookup;
                }
            }
        }
    }

    private function processForm( $postData, $columns ) {

        $post = apply_filters( "wordpresscrm_form_posted_data", $postData["entity"] );

        foreach ( $columns as $column ) {
            foreach ( $column["controls"] as $control ) {
                if ( isset( $this->entity->attributes[ $control->name ] ) && $this->entity->attributes[ $control->name ]->type == "Boolean" && !isset( $post[ $control->name ] ) ) {

                    $second = array_slice( $control->options, 1, 1, true );

                    $post[ $control->name ] = key( $second );
                }
            }
        }

        foreach ( $post as $key => $value ) {

            $value = trim( $value );

            if ( $this->entity->attributes[ $key ]->isValidForUpdate ) {

                $this->validateField( $key, $value );

                if ( $this->entity->attributes[ $key ]->isLookup ) {
                    if ( $value && $value != "NULL" ) {

                        $lookupEntity = null;

                        foreach ( $this->entity->attributes[ $key ]->lookupTypes as $lookupType ) {

                            if ( !$lookupEntity ) {
                                try {
                                    $lookupEntity = ASDK()->entity( $lookupType, $value );
                                    if ( !$lookupEntity->displayname ) {
                                        $lookupEntity = null;
                                        continue;
                                    }
                                } catch ( Exception $ex ) {
                                    $lookupEntity = null;
                                    continue;
                                }
                            }
                        }

                        if ( $lookupEntity ) {
                            $value = $lookupEntity;
                        } else {
                            array_push( $this->errors, sprintf( __( 'Unsupported lookup type for [%1$s], or entity {%2$s} not found', 'integration-dynamics' ), $key, $value ) );
                            $value = null;

                            return;
                        }
                    } else {
                        $value = null;
                    }

                    $this->entity->{$key} = $value;
                } else if ( $this->entity->attributes[ $key ]->type == "DateTime" ) {
                    $parsedValue = $value;
                    if ( $value ) {

                        $tzOffset = null;

                        $dateFormat = get_option( 'date_format' );
                        $dateTimeFormat = $dateFormat . ' ' . get_option( 'time_format' );
                        $parsedValue = \DateTime::createFromFormat( $dateFormat, $value );
                        if ( !$parsedValue) {
                            $parsedValue = \DateTime::createFromFormat( $dateTimeFormat, $value );
                        }
                        if ( !$parsedValue ) {
                            $parsedValue = strtotime( $value ) + $tzOffset;
                        } else {
                            $parsedValue = $parsedValue->getTimestamp();
                        }
                    } else {
                        $parsedValue = null;
                    }

                    $this->entity->{$key} = $parsedValue;
                } elseif ( $this->entity->attributes[ $key ]->optionSet instanceof Entity\OptionSet && $value !== '' ) {
                    $this->entity->{$key} = (int)$value;
                } else {

                    $this->entity->{$key} = stripslashes( $value );
                }
            } else if ( !$value ) {
                /* TODO: Add error handler */
            }
        }

        if ( is_array( $this->entityErrors ) ) {
            foreach ( $this->entityErrors as $key => $value ) {
                foreach ( $this->controls as $k => $column ) {
                    if ( isset( $this->controls[ $k ]["controls"][ $key ] ) ) {
                        $this->controls[ $k ]["controls"][ $key ]->error = $value;
                    }
                }
            }
        }
    }

    private function validateField( $field, $value ) {
        $errorsFound = false;

        foreach ( $this->controls as $column ) {
            if ( isset( $column["controls"][ $field ] ) ) {
                $control = $column["controls"][ $field ];
                break;
            }
        }

        if ( !isset( $control ) ) {
            return $errorsFound;
        }

        if ( $control->required && !$value ) {
            $this->entityErrors[ $field ] = $this->entity->getPropertyLabel( $field ) . " is required";
        }

        $property = $this->entity->attributes[ $field ];

        if ( $value ) {

            switch ( $property->type ) {

                case "String":

                    if ( $property->maxLength && ( strlen( $value ) > $property->maxLength ) ) {
                        $message                      = _n_noop( 'Must be less than %d characters', 'Must be less than %d characters', 'integration-dynamics' );
                        $this->entityErrors[ $field ] = sprintf( translate_nooped_plural( $message, $property->maxLength, 'integration-dynamics' ), $property->maxLength );
                    }

                    switch ( $property->format ) {
                        case "Text":
                            if ( $value && !$this->validator->validateItem( $value, 'anything' ) ) {
                                $this->entityErrors[ $field ] = __( 'Incorrect text value', 'integration-dynamics' );
                            }
                            break;
                        case "Email":
                            if ( $value && !$this->validator->validateItem( $value, 'email' ) ) {
                                $this->entityErrors[ $field ] = sprintf( __( '%s must be a valid email address', 'integration-dynamics' ), $control->label );
                            }
                            break;
                    }
                    break;
                case "Boolean":
                    break;
                case "Picklist":
                    break;
                case "Lookup":
                    break;
                case "Integer":
                    if ( $value && !$this->validator->validateItem( $value, 'amount' ) ) {
                        $this->entityErrors[ $field ] = __( 'Invalid number value', 'integration-dynamics' );
                    }
                    break;
                case "Double":
                    if ( $value && !$this->validator->validateItem( $value, 'float' ) ) {
                        $this->entityErrors[ $field ] = __( 'Invalid number value. Enter a decimal number without currency symbol or thousands separator.', 'integration-dynamics' );
                    }
                    break;
                case "Money":
                    if ( $value && !$this->validator->validateItem( $value, 'float' ) ) {
                        $this->entityErrors[ $field ] = __( 'Invalid money value. Enter a decimal number without currency symbol or thousands separator.', 'integration-dynamics' );
                    }
                    break;
                case "Memo":
                    break;
            }
        }

        if ( isset( $this->entityErrors[ $field ] ) ) {
            $errorsFound = true;
        }

        return $errorsFound;
    }

    public function printForm( $captcha = false ) {

        $args = array(
            'form'        => $this,
            'logicalname' => $this->entity->logicalname,
            'entity'      => $this->entity,
            'controls'    => $this->controls,
            'mode'        => $this->mode,
            'formname'    => $this->formName,
            'captcha'     => $captcha,
        );

        $path = ( $this->disableLayout ) ? 'form/inline-form' : 'form/form';

        $templatePath = ACRM()->getTemplate()->locateShortcodeTemplate( $path, $this->entity->logicalname, $this->formName );

        wp_enqueue_script( 'wordpresscrm-front', false, [], false, true );

        return ACRM()->getTemplate()->printTemplate( $templatePath, $args );
    }

    private function setupControls( $formXML ) {
        $properties = $this->entity->attributes;

        $formSimpleXML = simplexml_load_string( $formXML );

        $columnsXML = $formSimpleXML->xpath( ".//column" );
        $columns    = [ ];

        foreach ( $columnsXML as $columnXmlKey => $columnXML ) {
            /**
             * @var Control[] $controls
             */
            $controls = [];

            $section = $columnXML->xpath( ".//section" );

            $cellLabelAlignment = (string) $section[0]["celllabelalignment"];
            $cellLabelPosition = (string) $section[0]["celllabelposition"];

            $cells = $columnXML->xpath( ".//cell" );
            foreach ( $cells as $cell ) {
                $controlXml = $cell->xpath( ".//control" );

                if ( !isset( $controlXml[0] ) ) {
                    continue;
                }

                $control  = $controlXml[0];
                $name = strtolower( $control["id"] );

                if ( !isset( $properties[$name] ) ) {
                    continue;
                }

                $labelXml = $cell->xpath( ".//label" );
                $label = (string) $labelXml[0]["description"];

                $controls[ $name ]        = new Control( $name, $this->entity );
                $controls[ $name ]->label = ( $label ) ? $label : $this->entity->getPropertyLabel( $name );

                if ( $cellLabelAlignment !== '' ) {
                    $controls[$name]->labelAlignment = $cellLabelAlignment;

                    // left, center, or right
                    $controls[$name]->labelClass = 'text-' . strtolower( $cellLabelAlignment );
                }

                if ( $cellLabelPosition !== '' ) {
                    $controls[ $name ]->labelPosition = $cellLabelPosition;
                }

                $controls[ $name ]->type       = strtolower( $properties[ $name ]->type );
                $controls[ $name ]->recordName = null;

                if ( $properties[ $name ]->isLookup ) {
                    $controls[ $name ]->type       = 'lookup';
                    $controls[ $name ]->recordName = ( isset( $this->entity->{$name} ) && $this->entity->{$name} ) ? $this->entity->{$name}->displayname : null;

                    $controls[ $name ]->lookupTypes = array();

                    foreach ( $properties[ $name ]->lookupTypes as $entityLookupType ) {
                        $entityType                                          = ASDK()->entity( $entityLookupType );
                        $controls[ $name ]->lookupTypes[ $entityLookupType ] = $entityType->entityLogicalName;

                        if ( !array_key_exists( $name, $this->lookupTypes ) ) {
                            continue;
                        }

                        foreach ( $this->lookupTypes[ $name ] as $lookupType ) {
                            if ( $lookupType == $entityLookupType ) {
                                $entityType                                          = ASDK()->entity( $entityLookupType );
                                $controls[ $name ]->lookupTypes[ $entityLookupType ] = $entityType->entityLogicalName;
                            }
                        }
                    }
                }

                if ( !empty( $this->lookupViews[ $name ] ) ) {

                    $controls[ $name ]->type = 'lookup-picklist';

                    $lookupCacheKey = 'wpcrm_view_' . sha1( 'lookup_' . $name . '_' . $this->lookupViews[ $name ][0] );
                    $lookupViewFetch = ACRM()->getCache()->get( $lookupCacheKey );

                    if ( $lookupViewFetch == null ) {
                        $fetchView = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
                                                <entity name="savedquery">
                                                  <attribute name="name" />
                                                  <attribute name="fetchxml" />
                                                  <filter type="and">
                                                        <condition attribute="name" operator="eq" value="' . $this->lookupViews[ $name ][0] . '" />
                                                  </filter>
                                                </entity>
                                          </fetch>';

                        $lookupView = ASDK()->retrieveSingle( $fetchView );
                        $lookupViewFetch = $lookupView->fetchxml;
                        ACRM()->getCache()->set( $lookupCacheKey, $lookupViewFetch, 2 * 60 * 60 * 24 );
                    }

                    $dataCacheKey = 'wpcrm_data_' . sha1( $lookupViewFetch );
                    $options = ACRM()->getCache()->get( $dataCacheKey );
                    if ( $options == null ) {
                        $options = ASDK()->retrieveMultiple( $lookupViewFetch );
                        ACRM()->getCache()->set( $dataCacheKey, $options, 2 * 60 * 60 * 24 );
                    }

                    foreach ( $options->Entities as $optionEntity ) {
                        $controls[ $name ]->options[ $optionEntity->ID ] = $optionEntity->displayname;
                    }
                }

                $controls[ $name ]->classid = strtoupper( (string) $control['classid'] );

                if ( $properties[ $name ]->type == 'Picklist' || $properties[ $name ]->type == 'Boolean' ) {

                    $controls[ $name ]->options = $properties[ $name ]->optionSet->options;
                } else if ( empty( $controls[ $name ]->options ) ) {
                    $controls[ $name ]->options = null;
                }

                $controls[ $name ]->showlabel = false;
                if ( !$cell["showlabel"] || (string) $cell["showlabel"] == "true" ) {
                    $controls[ $name ]->showlabel = true;
                }

                $controls[ $name ]->readonly = (bool) ( $this->mode == "readonly" );

                if ( strtolower( $control['disabled'] ) == "true" ) {
                    $controls[ $name ]->readonly = true;
                }

                if ( isset( $cell["visible"] ) && $cell["visible"] == "false" ) {
                    $controls[ $name ]->visible = false;
                }

                $controls[ $name ]->fromEntity( $this->entity, $this->mode );

                if ( $name == "fullname" ) {
                    $controls["firstname"] = clone $controls[ $name ];
                    $controls["lastname"]  = clone $controls[ $name ];

                    $controls["firstname"]->name      = "firstname";
                    $controls["firstname"]->inputname = "entity[firstname]";
                    $controls["firstname"]->label     = "First name";
                    $controls["firstname"]->disabled  = false;
                    $controls["firstname"]->required = false;

                    if ( $properties["firstname"]->requiredLevel != 'None' && $properties["firstname"]->requiredLevel != 'Recommended' ) {
                        $controls["firstname"]->required = true;
                        $controls["firstname"]->jsValidators['required'] = [
                            'value'   => true,
                            'message' => sprintf( __( '%s is required', 'integration-dynamics' ), $controls["lastname"]->label ),
                        ];
                    }

                    $controls["lastname"]->name      = "lastname";
                    $controls["lastname"]->inputname = "entity[lastname]";
                    $controls["lastname"]->label     = "Last name";
                    $controls["lastname"]->disabled  = false;
                    $controls["lastname"]->required = true; // required by Dynamics CRM
                    $controls["lastname"]->jsValidators['required'] = [
                        'value'   => true,
                        'message' => sprintf( __( '%s is required', 'integration-dynamics' ), $controls["lastname"]->label ),
                    ];

                    unset( $controls[ $name ] );
                }

                /* Replace address memo control with editable address containing controls */
                if ( strpos( $name, "_composite" ) && $this->mode != "readonly" ) {
                    $controls = array_merge( $controls, $this->getCompositeAddressControls( $controls[ $name ] ) );
                    unset( $controls[ $name ] );
                }
                $control = apply_filters( "wordpresscrm_form_" . $this->formName . "_control_" . $name, $control );
            }

            $columns[ $columnXmlKey ]["attributes"] = current( $columnXML[0]->attributes() );
            $columns[ $columnXmlKey ]["controls"]   = apply_filters( "wordpresscrm_setup_form_controls", $controls );
        }

        end( $columns );
        $k = key( $columns );

        /* TODO: Return Attachment control to form */
        $columns[ $k ]["controls"] = $this->addNotesControl( $columns[ $k ]["controls"] );

        return apply_filters( "wordpresscrm_setup_form_columns", $columns );
    }

    private function addNotesControl( $controls ) {
        $annotationSupported = false;
        foreach ( $this->entity->oneToManyRelationships as $oneToManyRelationship ) {
            if ( $oneToManyRelationship->referencingEntity == "annotation" ) {
                $annotationSupported = true;
                break;
            }
        }

        if ( $this->attachment === false || ( !$annotationSupported && $this->attachment === null ) ) {
            unset( $controls["notescontrol"] );

            return $controls;
        }

        /* Adding notes control to form if needed */
        if ( $this->attachment === true && !array_key_exists( 'notescontrol', $controls ) ) {
            $controls["notescontrol"]            = new Control( "notescontrol" );
            $controls["notescontrol"]->type      = "attachment";
            $controls["notescontrol"]->showlabel = false;
        }

        /* Add attachment label */
        if ( isset( $controls["notescontrol"] ) && $this->attachmentLabel ) {
            $controls["notescontrol"]->label     = $this->attachmentLabel;
            $controls["notescontrol"]->showlabel = true;
        }

        return $controls;
    }

    private function getCompositeAddressControls( $control ) {

        $addressArray = array(
            'line1',
            'line2',
            'line3',
            'city',
            'stateorprovince',
            'postalcode',
            'country',
        );

        $compositePrefix = str_replace( "composite", "", $control->name );

        $controls = array();
        foreach ( $addressArray as $addr ) {
            if ( isset( $this->entity->{$compositePrefix . $addr} ) ) {
                $controls[ $compositePrefix . $addr ]        = new Control( $compositePrefix . $addr );
                $controls[ $compositePrefix . $addr ]->type  = $this->entity->attributes[ $compositePrefix . $addr ]->type;
                $controls[ $compositePrefix . $addr ]->label = $this->entity->getPropertyLabel( $compositePrefix . $addr );
                $controls[ $compositePrefix . $addr ]->value = $this->entity->{$compositePrefix . $addr};
            }
        }

        return $controls;
    }

    private static function enqueueFormScripts() {
        wp_enqueue_script( 'jquery-ui-widget' );
        wp_enqueue_script( 'jquery-ui-tooltip' );
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'jquery-ui-button' );
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        wp_enqueue_script( 'jquery-validation' );

        wp_enqueue_style( 'jquery-style', ACRM()->getPluginURL() . '/resources/front/css/wordpresscrm-jqueryui-css.css' );
    }

    /**
     * Retrieves a form record from Dynamics CRM.
     *
     * @param string $formName
     *
     * @return Entity
     */
    private function getFormEntity( $formName ) {
        $fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
                    <entity name="systemform">
                            <attribute name="objecttypecode"/>
                            <attribute name="name"/>
                            <attribute name="formxml"/>
                            <filter type="and">
                              <condition attribute="objecttypecode" operator="eq" value="' . $this->entity->metadata()->objectTypeCode . '" />
                              <condition attribute="name" operator="eq" value="' . $formName . '" />';
        if ( $this->formType ) {
            $fetch .= '<condition attribute="typename" operator="eq" value="' . $this->formType . '" />';
        }
        $fetch .= '</filter>;
                        </entity>
                  </fetch>';

        return ASDK()->retrieveSingle( $fetch );
    }

    /**
     * Retrieves FormXML from the CRM and caches it for 48 hours.
     *
     * @param string $formName
     *
     * @return string
     */
    private function getFormXML( $formName ) {
        $cacheKey = 'wpcrm_form_' . sha1( $this->entity->logicalname . '_form_' . $formName );
        $cache = ACRM()->getCache();

        $formXML = $cache->get( $cacheKey );
        if ( $formXML == null ) {
            $form = $this->getFormEntity( $formName );

            $formXML = $form->formXML;
            $cache->set( $cacheKey, $formXML, 2 * 60 * 60 * 24 );
        }

        return $formXML;
    }

}
