<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Form;

use AlexaCRM\CRMToolkit\Entity;
use AlexaCRM\WordpressCRM\Template;
use AlexaCRM\WordpressCRM\Messages;
use AlexaCRM\WordpressCRM\FormValidator;
use Exception;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class FormInstance extends AbstractForm {

    private $attributes;

    public $captcha = null;

    private $errors = [ ];

    private $notices = [ ];

    private $formName = null;

    private $formType = null;

    private $mode;

    /**
     * @var Entity
     */
    public $entity = null;

    public $controls = [ ];

    private $attachment = false;

    private $attachmentLabel = "";

    private $default = [ ];

    private $default_mode = [ ];

    private $lookupTypes = [ ];

    private $lookupViews = [ ];

    private $requiredFields = [ ];

    private $optionalFields = [ ];

    private $disableDefaultForCreate = false;

    private $disableDefaultForEdit = false;

    /**
     * @var FormValidator
     */
    private $validator = null;

    private $entityErrors = [ ];

    private $success_message = "<strong>Success!</strong>";

    private $showForm = true;

    private $formXML = null;

    private $disableLayout = false;

    private $formUid;

    private $ajax = false;

    public function __construct() {
        $this->captcha   = new GCaptcha();
        $this->validator = new FormValidator();
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
            case "attributes":
                return $this->attributes;
            case "ajax":
                return $this->ajax;
        }
    }

    private function getFormPostData( $ajax = false ) {
        $postData = null;
        if ( $ajax ) {
            parse_str( $_POST['form'], $formData );
        } else {
            $formData = $_POST;
        }
        if ( isset( $formData["entity"] ) &&
             isset( $formData["entity_form_entity"] ) &&
             isset( $formData["entity_form_name"] ) &&
             ( $formData["entity_form_name"] == $this->formName ) &&
             ( $formData["entity_form_entity"] == $this->entity->logicalname )
        ) {
            $postData = $formData;
        }

        return $postData;
    }

    /**
     * @param string $entity_name $entity the Entity to retrieve - must have an ID specified
     * @param string $form_name
     * @param string $mode
     * @param string $parameter_name
     */
    public function shortcode( $atts, $ajax = false, $_ = null ) {
        /* Check CRM connection */
        if ( !ACRM()->connected() ) {
            return self::notConnected();
        }
        try {
            /* Parse shortcode atts */
            $this->attributes = self::parseShortcodeAttributes( $atts );
            /* Lowercase mode parameter */
            $this->mode = self::parseModeAttribute( $this->attributes["mode"], $this->attributes["parameter_name"] );
            /* Disable default values when in create mode or in creade mode for upsert */
            $this->disableDefaultForCreate = ( $this->attributes["disable_default_for_create"] == "false" || !$this->attributes["disable_default_for_create"] ) ? false : true;
            /* Disable default values when in edit mode or in edit mode for upsert */
            $this->disableDefaultForEdit = ( $this->attributes["disable_default_for_edit"] == "false" || !$this->attributes["disable_default_for_edit"] ) ? false : true;
            /* Get default values array Entity form field as key, value definition as value ("value", "currentuser", "currentuser.field") */
            $this->default = self::parseDefaultAttribute( $this->attributes["default"] );
            /* Parse the mode attributes for default values */
            $this->default_mode = self::parseKeyArrayShortcodeAttribute( $this->attributes["default_mode"] );
            /* Get ajax shortcode attribute */
            $this->ajax = $this->attributes["ajax"];
            /* Restrict entity types for lookup fields */
            $this->lookupTypes = self::parseLookupTypesAttribute( $this->attributes["lookuptypes"] );
            /* Set custom lookup views for fields */
            $this->lookupViews = self::parseLookupTypesAttribute( $this->attributes["lookupviews"] );
            /* Parse required entity fields */
            $this->requiredFields = self::parseFieldPropertiesAttributes( $this->attributes["required"] );
            /* Parse optional entity fields */
            $this->optionalFields = self::parseFieldPropertiesAttributes( $this->attributes["optional"] );
            /* Detect need to use attachments */
            $this->attachment = self::parseAttachmentAttribute( $this->attributes["attachment"] );
            /* Use captcha */
            $captcha = self::parseCaptchaAttribute( $this->attributes["captcha"] );

            $this->formName = strtolower( $this->attributes["form_name"] );

            $this->formType = $this->attributes["form_type"];
            /* Generate form unique ID for fronend validation */
            $this->formUid         = uniqid( "entity-form-" );
            $entityName            = strtolower( $this->attributes["entity_name"] );
            $this->success_message = ( $this->attributes["message"] ) ? $this->attributes["message"] : $this->success_message;
            $redirect_url          = $this->attributes["redirect_url"];
            /* Check hide_form attribute exists and it value equals to "true" */
            $hide_form = ( $this->attributes["hide_form"] && $this->attributes["hide_form"] == "true" );
            /* Parse attachment label attribute for notescontrol form control label */
            $this->attachmentLabel = $this->attributes["attachment_label"];

            $this->disableLayout = ( $this->attributes["enable_layout"] != "true" );
            /* Retrieve parameter name */
            $id = self::parseParameterName( $this->attributes["parameter_name"], $this->mode );
            /* Retrieve record entity based on parameter name, if ID is defined entity will be data filled */
            if ( isset( $id ) && $id ) {
                $this->entity = ASDK()->entity( $entityName, $id );
            } else {
                $this->entity = ASDK()->entity( $entityName );
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
                array_push( $this->errors, "Can not get form definition" );

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
                        array_push( $this->errors, ( $this->attributes["validation_error"] ) ? $this->attributes["validation_error"] : Messages::getMessage( "form", "validation_error" ) );
                    }

                    if ( $this->captcha->enable_captcha && ( !$this->captcha->checkResponse() || !$this->captcha->checkCaptcha() ) ) {
                        array_push( $this->errors, Messages::getMessage( "form", "invalid_captcha" ) );
                    }

                    $this->errors = apply_filters( "wordpresscrm_form_errors", $this->errors );

                    if ( empty( $this->errors ) ) {
                        try {
                            $result = false;

                            if ( $this->mode == "edit" ) {
                                $result = ASDK()->update( $this->entity );
                            } else {
                                $result = ASDK()->create( $this->entity );
                            }

                            if ( !$result ) {
                                array_push( $this->errors, ( $this->attributes["submit_error"] ) ? $this->attributes["submit_error"] : Messages::getMessage( "form", "crm_error" ) );
                            } else {
                                $objectid = ( $this->mode == "edit" ) ? $this->entity->id : $result;
                                $this->proccessAttachments( $objectid );

                                if ( $redirect_url ) {
                                    ACRM()->javascript_redirect( $redirect_url );
                                }

                                array_push( $this->notices, $this->success_message );

                                $this->showForm = !$hide_form;
                            }
                        } catch ( Exception $ex ) {
                            array_push( $this->errors, $ex->getMessage() );
                        }
                    } else if ( $this->mode == "create" ) {
                        $this->controls = self::setValuesToControls( $this->controls, $this->entity );
                    }
                }
            }

            return apply_filters( "wordpresscrm_form_print_form", $this->printForm( $captcha ) );
        } catch ( Exception $ex ) {
            return self::returnExceptionError( $ex );
        }
    }

    private function proccessAttachments( $entity ) {

        if ( isset( $_FILES['entity'] ) && isset( $_FILES['entity']['name']['notescontrol'] ) && $_FILES['entity']['name']['notescontrol'] ) {
            $file_name = $_FILES['entity']['name']['notescontrol'];
            $file_size = $_FILES['entity']['size']['notescontrol'];
            $file_tmp  = $_FILES['entity']['tmp_name']['notescontrol'];
            $file_type = $_FILES['entity']['type']['notescontrol'];

            $type   = pathinfo( $file_tmp, PATHINFO_EXTENSION );
            $base64 = base64_encode( file_get_contents( $file_tmp ) );

            $newAnnotation = ASDK()->entity( 'annotation' );

            if ( $entity instanceof Entity ) {
                $newAnnotation->objectid = $entity;
            } else {
                $entityObject            = ASDK()->entity( $this->entity->logicalname );
                $entityObject->id        = $entity;
                $newAnnotation->objectid = $entityObject;
            }
            $newAnnotation->subject      = "Attachment file " . $file_name;
            $newAnnotation->documentbody = $base64;
            $newAnnotation->mimetype     = $file_type;
            $newAnnotation->filename     = $file_name;

            $annocation = ASDK()->create( $newAnnotation );
        }
    }

    private function setupDefaultControlsAndValues() {

        if ( ( $this->disableDefaultForCreate && $this->mode == "create" ) || ( $this->disableDefaultForEdit && $this->mode == "edit" ) ) {
            return;
        }

        $counter = - 1;
        foreach ( $this->default as $key => $value ) {
            $counter ++;
            if ( isset( $this->default_mode[ $counter ] ) &&
                 strtolower( $this->default_mode[ $counter ] != "upsert" ) &&
                 strtolower( $this->default_mode[ $counter ] ) != $this->mode
            ) {
                continue;
            }

            end( $this->controls );
            $last = key( $this->controls );
            reset( $this->controls );

            $k = null;

            foreach ( $this->controls as $colomnKey => $col ) {
                if ( isset( $this->controls[ $colomnKey ]["controls"][ $key ] ) ) {
                    $k = $key;
                }
            }

            if ( isset( $this->entity->{$key} ) ) {
                try {
                    /* Check $_GET parameter for defaults */
                    if ( strpos( $value, '.' ) !== false ) {
                        if ( strpos( $value, 'querystring' ) ) {
                            $explode = explode( ".", $value );

                            $qparams = $this->parseQueryString();

                            if ( isset( $qparams[ $explode[1] ] ) && $qparams[ $explode[1] ] ) {

                                $queryvalue = $qparams[ $explode[1] ];

                                if ( $k == null ) {
                                    $this->controls[ $last ]["controls"][ $key ]          = new Control( $key );
                                    $this->controls[ $last ]["controls"][ $key ]->visible = false;
                                    $this->controls[ $last ]["controls"][ $key ]->value   = $queryvalue;
                                    $this->entity->{$key}                                 = $queryvalue;
                                } else {
                                    /* Simple value string */
                                    if ( $this->entity->attributes[ $key ]->isLookup ) {
                                        foreach ( $this->entity->attributes[ $key ]->lookupTypes as $entityType ) {
                                            try {
                                                $lookup = ASDK()->entity( $entityType, $queryvalue );
                                            } catch ( Exception $ex ) {
                                                continue;
                                            }
                                            $this->entity->{$key} = $lookup;
                                        }
                                    } else {
                                        $this->entity->{$key} = $queryvalue;
                                    }
                                }
                            } else {
                                unset( $this->default[ $key ] );
                            }
                        }

                        do_action( 'wordpresscrm_form_setup_with_comma', $this, $value, $k, $key, $last );
                    } else {
                        $setupDefault = apply_filters( 'wordpresscrm_form_setup_without_comma', true, $this, $value, $k, $key, $last );

                        if ( $setupDefault ) {
                            if ( $k == null ) {

                                $this->controls[ $last ]["controls"][ $key ]          = new Control( $key );
                                $this->controls[ $last ]["controls"][ $key ]->visible = false;
                                $this->controls[ $last ]["controls"][ $key ]->value   = $value;
                                $this->entity->{$key}                                 = $value;
                            } else {
                                /* Simple value string */
                                if ( $this->entity->attributes[ $key ]->isLookup ) {
                                    foreach ( $this->entity->attributes[ $key ]->lookupTypes as $entityType ) {
                                        try {

                                            $lookup = ASDK()->entity( $entityType, $value );
                                        } catch ( Exception $ex ) {
                                            continue;
                                        }

                                        $this->entity->{$key} = $lookup;
                                    }
                                } else {
                                    $this->entity->{$key} = $value;
                                }
                            }
                        }
                    }
                } catch ( Exception $ex ) {
                    array_push( $this->errors, $ex->getMessage() );

                    return self::printFormErrors( $this->errors );
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

                    if ( $value ) {

                        $timezoneoffset = null;

                        if ( isset( $_SESSION["bearer"] ) ) {
                            $timezoneoffset = ( isset( $_SESSION["bearer"]["timezonebias"] ) ) ? $_SESSION["bearer"]["timezonebias"] : null;
                        }

                        if ( isset( $_SESSION["alexaWPSDK"] ) ) {
                            $timezoneoffset = ( isset( $_SESSION["alexaWPSDK"]["timezoneoffset"] ) ) ? $_SESSION["alexaWPSDK"]["timezoneoffset"] : null;
                        }

                        $value = strtotime( $value ) + $timezoneoffset;
                    } else {
                        $value = null;
                    }

                    $this->entity->{$key} = $value;
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
            }
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
                                $this->entityErrors[ $field ] = __( 'Incorrect email', 'integration-dynamics' );
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

        $templatePath = Template::locateShortcodeTemplate( $path, $this->entity->logicalname, $this->formName );

        return Template::printTemplate( $templatePath, $args );
    }

    private function setupControls( $formXML ) {

        $properties = $this->entity->attributes;

        $formSimpleXML = simplexml_load_string( $formXML );

        $columnsXML = $formSimpleXML->xpath( ".//column" );
        $columns    = [ ];

        foreach ( $columnsXML as $columnXmlKey => $columnXML ) {
            $controls = [ ];

            $section = $columnXML->xpath( ".//section" );

            $cellLabelAligment = (string) $section[0]["celllabelalignment"];
            $cellLabelPosition = (string) $section[0]["celllabelposition"];

            $cells = $columnXML->xpath( ".//cell" );
            foreach ( $cells as $cell ) {
                $controlXml = $cell->xpath( ".//control" );

                if ( isset( $controlXml[0] ) ) {
                    $control  = $controlXml[0];
                    $labelXml = $cell->xpath( ".//label" );

                    $label = (string) $labelXml[0]["description"];

                    $name = strtolower( $control["id"] );

                    if ( isset( $properties[ $name ] ) ) {
                        $controls[ $name ]        = new Control( $name, $this->entity );
                        $controls[ $name ]->label = ( $label ) ? $label : $this->entity->getPropertyLabel( $name );

                        if ( isset( $cellLabelAligment ) ) {
                            $controls[ $name ]->labelAligment = $cellLabelAligment;

                            switch ( $controls[ $name ]->labelAligment ) {
                                case "Left":
                                    $controls[ $name ]->labelClass = "text-left";
                                    break;
                                case "Center":
                                    $controls[ $name ]->labelClass = "text-center";
                                    break;
                                case "Right":
                                    $controls[ $name ]->labelClass = "text-right";
                                    break;
                            }
                        }

                        if ( isset( $cellLabelPosition ) ) {
                            $controls[ $name ]->labelPosition = $cellLabelPosition;
                        }

                        if ( $properties[ $name ]->isLookup ) {
                            $controls[ $name ]->type       = 'lookup';
                            $controls[ $name ]->recordName = ( isset( $this->entity->{$name} ) && $this->entity->{$name} ) ? $this->entity->{$name}->displayname : null;

                            $controls[ $name ]->lookupTypes = array();

                            if ( key_exists( $name, $this->lookupTypes ) ) {
                                foreach ( $properties[ $name ]->lookupTypes as $entityLookupType ) {
                                    foreach ( $this->lookupTypes[ $name ] as $lookupType ) {
                                        if ( $lookupType == $entityLookupType ) {
                                            $entityType                                          = ASDK()->entity( $entityLookupType );
                                            $controls[ $name ]->lookupTypes[ $entityLookupType ] = $entityType->entityLogicalName;
                                        }
                                    }
                                }
                            } else {
                                foreach ( $properties[ $name ]->lookupTypes as $entityLookupType ) {
                                    $entityType                                          = ASDK()->entity( $entityLookupType );
                                    $controls[ $name ]->lookupTypes[ $entityLookupType ] = $entityType->entityLogicalName;
                                }
                            }
                        } else {
                            $controls[ $name ]->type       = strtolower( $properties[ $name ]->type );
                            $controls[ $name ]->recordName = null;
                        }

                        if ( !empty( $this->lookupViews[ $name ] ) ) {

                            $controls[ $name ]->type = 'lookup-picklist';

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

                            $options = ASDK()->retrieveMultiple( $lookupView->fetchxml );

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

                        if ( !$cell["showlabel"] || (string) $cell["showlabel"] == "true" ) {
                            $controls[ $name ]->showlabel = true;
                        } else {
                            $controls[ $name ]->showlabel = false;
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
                            $arr = (array) $controls[ $name ];

                            $controls["firstname"] = (object) $arr;
                            $controls["lastname"]  = (object) $arr;

                            $controls["firstname"]->name      = "firstname";
                            $controls["firstname"]->inputname = "entity[firstname]";
                            $controls["firstname"]->label     = "First name";
                            $controls["firstname"]->disabled  = false;
                            if ( $properties["firstname"]->requiredLevel != 'None' && $properties["firstname"]->requiredLevel != 'Recommended' ) {
                                $controls["firstname"]->required = true;
                            } else {
                                $controls["firstname"]->required = false;
                            }

                            $controls["lastname"]->name      = "lastname";
                            $controls["lastname"]->inputname = "entity[lastname]";
                            $controls["lastname"]->label     = "Last name";
                            $controls["lastname"]->disabled  = false;

                            if ( $properties["lastname"]->requiredLevel != 'None' && $properties["lastname"]->requiredLevel != 'Recommended' ) {
                                $controls["lastname"]->required = true;
                            } else {
                                $controls["lastname"]->required = false;
                            }
                            unset( $controls[ $name ] );
                        }

                        /* Replace address memo control with editable address containing controls */
                        if ( strpos( $name, "_composite" ) && $this->mode != "readonly" ) {
                            $controls = array_merge( $controls, $this->getCompositeAddressControls( $controls[ $name ] ) );
                            unset( $controls[ $name ] );
                        }
                        $control = apply_filters( "wordpresscrm_form_" . $this->formName . "_control_" . $name, $control );
                    }
                }
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
        /* Adding notes control to form if needed */
        if ( array_key_exists( 'notescontrol', $controls ) && $this->attachment == "null" ) {
            // Do nothing, default display attachment form if it's exists
        } else if ( array_key_exists( 'notescontrol', $controls ) && $this->attachment == "false" ) {
            unset( $controls["notescontrol"] );
        } else if ( $this->attachment == "true" && !array_key_exists( 'notescontrol', $controls ) ) {
            $controls["notescontrol"]            = new Control( "notescontrol" );
            $controls["notescontrol"]->type      = "attachment";
            $controls["notescontrol"]->showlabel = false;
        }
        /* Add attachment label */
        if ( isset( $controls["notescontrol"] ) && $this->attachmentLabel ) {
            $controls["notescontrol"]->label     = $this->attachmentLabel;
            $controls["notescontrol"]->showlabel = true;
        }

        $annotationSupported = false;
        foreach ( $this->entity->oneToManyRelationships as $oneToManyRelationship ) {
            if ( $oneToManyRelationship->referencingEntity == "annotation" ) {
                $annotationSupported = true;
            }
        }

        if ( !$annotationSupported && $this->attachment != "true" ) {
            unset( $controls["notescontrol"] );
        } elseif ( $annotationSupported && $this->attachment == "false" ) {
            unset( $controls["notescontrol"] );
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

        wp_enqueue_style( 'jquery-style', ACRM()->plugin_url() . '/resources/front/css/wordpresscrm-jqueryui-css.css' );
    }

    public static function getFormEntity( $formName, $entity, $formType = null ) {
        $fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
                    <entity name="systemform">
                            <attribute name="objecttypecode"/>
                            <attribute name="name"/>
                            <attribute name="formxml"/>
                            <filter type="and">
                              <condition attribute="objecttypecode" operator="eq" value="' . $entity->metadata()->objectTypeCode . '" />
                              <condition attribute="name" operator="eq" value="' . $formName . '" />';
        if ( $formType ) {
            $fetch .= '<condition attribute="typename" operator="eq" value="' . $formType . '" />';
        }
        $fetch .= '</filter>;
                        </entity>
                  </fetch>';

        return ASDK()->retrieveSingle( $fetch );
    }

    private function getFormXML( $formName ) {

        $formxml = ACRM()->cache->get( "formxml_" . $this->entity->logicalname . str_replace( " ", "", $formName ) );

        if ( $formxml == null ) {
            $form = self::getFormEntity( $formName, $this->entity, $this->formType );

            $formxml = $form->formXML;

            ACRM()->cache->set( "formxml_" . $this->entity->logicalname . str_replace( " ", "", $formName ), $formxml, 28800 );
        }

        return $formxml;
    }

}
