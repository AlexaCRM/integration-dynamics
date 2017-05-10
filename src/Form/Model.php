<?php

namespace AlexaCRM\WordpressCRM\Form;

use AlexaCRM\CRMToolkit\Client;
use AlexaCRM\CRMToolkit\Entity;
use AlexaCRM\WordpressCRM\FormValidator;

/**
 * Represents a Dynamics CRM Form.
 */
class Model {

    /**
     * Entity that the form belongs to.
     *
     * @var string
     */
    public $entityName;

    /**
     * @var string
     */
    public $formName;

    /**
     * @var array
     */
    public $attributes;

    /**
     * @var string
     */
    private $instanceId;

    /**
     * The point in time when the form was created.
     *
     * @var int
     */
    private $birthday;

    /**
     * @var Entity
     */
    private $record;

    /**
     * List of form controls.
     *
     * @var array
     */
    private $formControls;

    /**
     * Model constructor.
     */
    public function __construct() {
        $this->instanceId = Client::getUuid();
        $this->birthday = time();
    }

    /**
     * Creates a form model for the given form.
     *
     * @param string $entityName
     * @param string $formName
     * @param array $attributes
     *
     * @return Model
     */
    public static function buildModel( $entityName, $formName, $attributes = [] ) {
        $model = new static();

        $model->entityName = $entityName;
        $model->formName = $formName;
        $model->record = ASDK()->entity( $entityName );

        if ( !array_key_exists( 'optional', $attributes ) || !is_array( $attributes['optional'] ) ) {
            $attributes['optional'] = [];
        }

        if ( !array_key_exists( 'required', $attributes ) || !is_array( $attributes['required'] ) ) {
            $attributes['required'] = [];
        }

        if ( !array_key_exists( 'record', $attributes ) ) {
            $attributes['record'] = null;
        }

        $model->attributes = $attributes;

        return $model;
    }

    /**
     * Registers the form in the session to process submission later.
     */
    public function registerHandler() {
        Controller::registerFormHandler( $this );
    }

    /**
     * Builds the view of the form.
     */
    public function buildView() {
        $formXML = $this->retrieveFormXml();
        if ( $formXML === null  ) {
            return [];
        }

        $metadata = ACRM()->getMetadata()->getEntityDefinition( $this->entityName );
        $formDefinition = [
            'id' => $this->getInstanceId(),
            'name' => $this->formName,
            'tabs' => [],
            'options' => [
                'dateformat' => get_option( 'date_format' ),
                'datetimeformat' => get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
            ],
            'metadata' => $metadata,
        ];

        $formDOM = new \DOMDocument();
        $formDOM->loadXML( $formXML );
        $formXPath = new \DOMXPath( $formDOM );

        $columnSet = [];

        $tabs = $formXPath->query( '/form/tabs/tab' );
        foreach ( $tabs as $tab ) {
            $tabId = $tab->getAttribute( 'id' );
            $tabDefinition = [
                'showLabel' => ( $tab->getAttribute( 'showlabel' ) === 'true' ),
                'expanded' => ( $tab->getAttribute( 'expanded' ) === 'true' ),
                'label' => $formXPath->evaluate( 'string(./labels/label/@description[1])', $tab ),
                'columns' => [],
            ];

            $columns = $formXPath->query( './columns/column', $tab );
            foreach ( $columns as $column ) {
                $columnDefinition = [
                    'width' => (int)trim( $column->getAttribute( 'width' ), '%' ),
                    'sections' => [],
                ];

                $sections = $formXPath->query( './sections/section', $column );
                foreach ( $sections as $section ) {
                    $sectionId = $section->getAttribute( 'id');
                    $sectionDefinition = [
                        'showLabel' => ( $section->getAttribute( 'showlabel' ) === 'true' ),
                        'label' => $formXPath->evaluate( 'string(./labels/label/@description[1])', $section ),
                        'cellLabelAlignment' => strtolower( $section->getAttribute( 'celllabelalignment' ) ),
                        'cellLabelPosition' => strtolower( $section->getAttribute( 'celllabelposition' ) ),
                        'rows' => [],
                    ];

                    $rows = $formXPath->query( './rows/row[count(*)>0]', $section );
                    foreach ( $rows as $row ) {
                        $cellCollection = [];

                        $cells = $formXPath->query( './cell', $row );
                        foreach ( $cells as $cell ) {
                            $cellId = $cell->getAttribute( 'id' );
                            $cellDefinition = [
                                'showLabel' => ( $cell->getAttribute( 'showlabel' ) === 'true' ),
                                'label' => $formXPath->evaluate( 'string(./labels/label/@description[1])', $cell ),
                                'colspan' => (int)$cell->getAttribute( 'colspan' ),
                                'rowspan' => (int)$cell->getAttribute( 'rowspan' ),
                                'isSpacer' => ( $cell->getAttribute( 'userspacer' ) === 'true' ),
                            ];

                            $controlList = $formXPath->query( './control', $cell );
                            if ( $controlList->length ) {
                                $control = $controlList->item( 0 );
                                $controlDefinition = [
                                    'id' => $control->getAttribute( 'id' ),
                                    'classId' => strtoupper( $control->getAttribute( 'classid' ) ),
                                    'disabled' => ( $control->getAttribute( 'disabled' ) === 'true' ),
                                    'required' => false,
                                    'name' => $control->getAttribute( 'datafieldname' ),
                                    'metadata' => $metadata->attributes[$control->getAttribute( 'datafieldname' )],
                                    'parameters' => [],
                                ];

                                $this->formControls[] = $controlDefinition['name'];

                                $controlDefinition['required'] = in_array( $controlDefinition['metadata']->requiredLevel, [ 'ApplicationRequired', 'SystemRequired' ] );
                                if ( $controlDefinition['required'] && !in_array( $controlDefinition['name'], $this->attributes['optional'] ) ) {
                                    $controlDefinition['required'] = false;
                                }
                                if ( !$controlDefinition['required'] && in_array( $controlDefinition['name'], $this->attributes['required'] ) ) {
                                    $controlDefinition['required'] = true;
                                }

                                $columnSet[] = $controlDefinition['name'];

                                $parameters = $formXPath->query( './parameters/*', $control );
                                foreach ( $parameters as $parameter ) {
                                    $controlDefinition['parameters'][$parameter->nodeName] = $parameter->nodeValue;
                                }

                                $cellDefinition['control'] = $controlDefinition;
                            }

                            $cellCollection[$cellId] = $cellDefinition;
                        }

                        $sectionDefinition['rows'][] = $cellCollection;
                    }

                    $columnDefinition['sections'][$sectionId] = $sectionDefinition;
                }

                $tabDefinition['columns'][] = $columnDefinition;
            }

            $formDefinition['tabs'][$tabId] = $tabDefinition;
        }

        $formDefinition['record'] = $this->record;
        if ( $this->attributes['record'] instanceof Entity ) {
            $formDefinition['record'] = $this->record = $this->attributes['record'];
        } elseif ( is_string( $this->attributes['record'] ) && Client::isGuid( $this->attributes['record'] ) ) {
            $formDefinition['record'] = $this->record = ASDK()->entity( $this->entityName, $this->attributes['record'], $columnSet );
        }

        return $formDefinition;
    }

    /**
     * @param array $fields
     *
     * @return Entity
     */
    public function hydrateRecord( $fields ) {
        $metadata = ACRM()->getMetadata()->getEntityDefinition( $this->entityName );
        $fields = array_intersect_key( $fields, $metadata->attributes );

        foreach ( $fields as $fieldName => $fieldValue ) {
            $fieldValue = trim( $fieldValue );
            $fieldMetadata = $metadata->attributes[$fieldName];

            if ( !$fieldMetadata->isValidForUpdate ) {
                continue;
            }

            if ( $fieldMetadata->optionSet instanceof Entity\OptionSet && $fieldValue !== '' ) {
                $this->record->{$fieldName} = (int)$fieldValue;
                continue;
            }

            if ( $fieldMetadata->isLookup && $fieldValue !== '' ) {
                $reference = json_decode( $fieldValue );
                if ( is_array( $reference ) && count( $reference ) === 2 ) {
                    if ( !$reference[0] || !$reference[1] ) {
                        continue;
                    }

                    $entityReference = ASDK()->entity( $reference[0] );
                    $entityReference->ID = $reference[1];
                    $this->record->{$fieldName} = $entityReference;
                }

                continue;
            }

            if ( $fieldMetadata->type === 'DateTime' ) {
                if ( $fieldValue === '' ) {
                    $this->record->{$fieldName} = null;
                    continue;
                }

                $dateFormat = get_option( 'date_format' );
                $dateTimeFormat = $dateFormat . ' ' . get_option( 'time_format' );
                $parsedValue = \DateTime::createFromFormat( $dateFormat, $fieldValue );
                if ( !$parsedValue) {
                    $parsedValue = \DateTime::createFromFormat( $dateTimeFormat, $fieldValue );
                } else {
                    $parsedValue->setTime( 0, 0, 0 );
                }

                if ( !$parsedValue ) {
                    $parsedValue = strtotime( $fieldValue );
                } else {
                    $parsedValue = $parsedValue->getTimestamp();
                }

                $this->record->{$fieldName} = $parsedValue;

                continue;
            }

            $this->record->{$fieldName} = $fieldValue;
        }

        return $this->record;
    }

    /**
     * @return string
     */
    public function getInstanceId() {
        return $this->instanceId;
    }

    /**
     * Tells whether the form has expired.
     *
     * @return bool
     */
    public function hasExpired() {
        return ( ( time() - $this->birthday ) > 30 * MINUTE_IN_SECONDS );
    }

    /**
     * Validates the given array of data.
     *
     * @param array $fields
     *
     * @return array
     */
    public function validate( $fields ) {
        $metadata = ACRM()->getMetadata()->getEntityDefinition( $this->entityName );

        $controls = $this->formControls;

        // replace fullname with firstname and lastname
        if ( in_array( 'fullname', $controls, true ) ) {
            unset( $controls[array_search( 'fullname', $controls )] );
            $controls[] = 'firstname';
            $controls[] = 'lastname';
        }

        // replace addressN_composite with separate fields
        for ( $i = 1; $i <= 3; $i++ ) {
            if ( in_array( "address{$i}_composite", $controls ) ) {
                unset( $controls[array_search( "address{$i}_composite", $controls )] );
                $controls[] = "address{$i}_line1";
                $controls[] = "address{$i}_line2";
                $controls[] = "address{$i}_line3";
                $controls[] = "address{$i}_city";
                $controls[] = "address{$i}_stateorprovince";
                $controls[] = "address{$i}_postalcode";
                $controls[] = "address{$i}_country";
            }
        }

        /**
         * Allows altering the list of accepted fields.
         */
        $controls = apply_filters( 'wordpresscrm_form_controls', $controls );

        $errors = [];

        foreach ( $controls as $fieldName ) {
            $fieldMetadata = $metadata->attributes[$fieldName];

            $isRequired = in_array( $fieldMetadata->requiredLevel, [ 'ApplicationRequired', 'SystemRequired' ] ); // metadata based
            if ( $isRequired && in_array( $fieldName, $this->attributes['optional'] ) ) { // optional override
                $isRequired = false;
            }
            if ( !$isRequired && in_array( $fieldName, $this->attributes['required'] ) ) { // required override
                $isRequired = true;
            }

            if ( $isRequired && ( !array_key_exists( $fieldName, $fields ) || trim( $fields[$fieldName] ) === '' ) ) {
                $errors[$fieldName][] = __( 'The field is required.', 'integration-dynamics' );
                continue;
            }

            $validationResult = $this->validateFieldValue( $fieldMetadata, $fields[$fieldName] );
            if ( !$validationResult['status'] ) {
                $errors[$fieldName][] = $validationResult['payload'];
            }
        }

        $result = [
            'status' => false,
            'payload' => $errors,
        ];

        if ( !count( $errors ) ) {
            $result['status'] = true;
            $result['payload'] = array_intersect_key( $fields, $metadata->attributes ); // FIXME
        }

        return $result;
    }

    /**
     * @param Entity\Attribute $metadata
     * @param $value
     *
     * @return array
     */
    private function validateFieldValue( $metadata, $value ) {
        $result = [
            'status' => false,
            'payload' => __( 'The field could not be validated.', 'integration-dynamics' ),
        ];

        switch ( $metadata->type ) {
            case 'String':
                if ( $metadata->maxLength && mb_strlen( $value ) > $metadata->maxLength ) {
                    $message = _n_noop( 'Must be less than %d characters.', 'Must be less than %d characters.', 'integration-dynamics' );
                    $result['payload'] = sprintf( translate_nooped_plural( $message, $metadata->maxLength, 'integration-dynamics' ), $metadata->maxLength );
                    return $result;
                }

                switch ( $metadata->format ) {
                    case 'Text':
                      if ( $value !== '' && !FormValidator::validateItem( $value, 'anything' ) ) {
                          $result['payload'] = __( 'Incorrect text value.', 'integration-dynamics' );
                          return $result;
                      }

                      break;
                    case 'Email':
                        if ( $value !== '' && !FormValidator::validateItem( $value, 'email' ) ) {
                            $result['payload'] = __( 'Not a valid email address.', 'integration-dynamics' );
                            return $result;
                        }

                        break;
                }

                break;
            case 'Integer':
                if ( $value !== '' && !FormValidator::validateItem( $value, 'amount') ) {
                    $result['payload'] = __( 'Invalid number value.', 'integration-dynamics' );
                    return $result;
                }

                break;
            case 'Double':
            case 'Money':
                if ( $value !== '' && !FormValidator::validateItem( $value, 'float' ) ) {
                    $result['payload'] = __( 'Invalid decimal value.', 'integration-dynamics' );
                    return $result;
                }

                break;
        }

        $result = [ 'status' => true, 'payload' => null ];

        return $result;
    }

    /**
     * Retrieves FormXML for the current form from Dynamics 365.
     *
     * @return string|null
     */
    private function retrieveFormXml() {
        $cacheKey = 'wpcrm_form_' . sha1( $this->entityName . '_form_' . $this->formName );
        $cache = ACRM()->getCache();

        $formXML = $cache->get( $cacheKey );
        if ( $formXML == null ) {
            $entityMetadata = ACRM()->getMetadata()->getEntityDefinition( $this->entityName );

            $fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
                    <entity name="systemform">
                            <attribute name="objecttypecode"/>
                            <attribute name="name"/>
                            <attribute name="formxml"/>
                            <filter type="and">
                              <condition attribute="objecttypecode" operator="eq" value="' . $entityMetadata->objectTypeCode . '" />
                              <condition attribute="name" operator="eq" value="' . $this->formName . '" />
                            </filter>;
                        </entity>
                  </fetch>';

            $form = ASDK()->retrieveSingle( $fetch );
            if ( $form === null ) {
                return null;
            }

            $formXML = $form->formXML;
            $cache->set( $cacheKey, $formXML, 2 * 60 * 60 * 24 );
        }

        return $formXML;
    }

}
