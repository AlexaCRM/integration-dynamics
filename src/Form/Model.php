<?php

namespace AlexaCRM\WordpressCRM\Form;

use AlexaCRM\CRMToolkit\Client;
use AlexaCRM\CRMToolkit\Entity;
use AlexaCRM\WordpressCRM\FormValidator;
use AlexaCRM\WordpressCRM\View;

/**
 * Represents a Dynamics CRM Form.
 */
class Model {

    /**
     * Class ID for the custom lookup view control.
     */
    const CONTROL_LOOKUP_VIEW = '{628064E7-E104-4B65-9EBF-3ED02F9AEBB6}';

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

        $model->entityName = strtolower( $entityName );
        $model->formName = $formName;
        $model->record = ASDK()->entity( $model->entityName );

        if ( !isset( $attributes['key'] ) ) {
            $keyAttributes = $attributes;
            unset( $keyAttributes['record'] );
            $attributes['key'] = sha1( $entityName . $formName . serialize( $keyAttributes ) );
        }

        // list of fields to be marked as optional (overrides metadata)
        if ( !array_key_exists( 'optional', $attributes ) || !is_array( $attributes['optional'] ) ) {
            $attributes['optional'] = [];
        }

        // list of fields to be marked as required (overrides metadata)
        if ( !array_key_exists( 'required', $attributes ) || !is_array( $attributes['required'] ) ) {
            $attributes['required'] = [];
        }

        // map of default field values
        if ( !array_key_exists( 'default', $attributes ) || !is_array( $attributes['default'] ) ) {
            $attributes['default'] = [];
        }

        // lookup views
        if ( !array_key_exists( 'lookupviews', $attributes ) || !is_array( $attributes['lookupviews'] ) ) {
            $attributes['lookupviews'] = [];
        }

        // redirects for different actions
        if ( array_key_exists( 'redirect', $attributes ) && is_string( $attributes['redirect'] ) ) {
            $attributes['redirect'] = [ 'always' => $attributes['redirect'] ];
        }
        if ( !array_key_exists( 'redirect', $attributes ) || !is_array( $attributes['redirect'] ) ) {
            $attributes['redirect'] = [];
        }

        if ( !array_key_exists( 'record', $attributes ) ) {
            $attributes['record'] = null;
        }

        $model->attributes = $attributes;

        return $model;
    }

    /**
     * Builds the view of the form.
     */
    public function buildView() {
        $formDefinition = [
            'key' => $this->attributes['key'],
        ];

        $formDefinition['record'] = $this->record;
        if ( $this->attributes['record'] instanceof Entity ) {
            $formDefinition['record'] = $this->record = $this->attributes['record'];
        } elseif ( is_string( $this->attributes['record'] ) && Client::isGuid( $this->attributes['record'] ) ) {
            $formDefinition['record'] = $this->record = ASDK()->entity( $this->entityName, $this->attributes['record'] );
        }

        $formXML = $this->retrieveFormXml();
        if ( $formXML === null  ) {
            return $formDefinition;
        }

        $metadata = ACRM()->getMetadata()->getEntityDefinition( $this->entityName );
        $formDefinition = [
            'id' => $this->getInstanceId(),
            'key' => $this->attributes['key'],
            'name' => $this->formName,
            'tabs' => [],
            'options' => [
                'dateformat' => get_option( 'date_format' ),
                'datetimeformat' => get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
            ],
            'metadata' => $metadata,
            'parameters' => $this->attributes,
            'record' => $formDefinition['record'],
        ];

        $formDOM = new \DOMDocument();
        $formDOM->loadXML( $formXML );
        $formXPath = new \DOMXPath( $formDOM );

        $notSupportedControlClasses = [
            '{06375649-C143-495E-A496-C962E5B4488E}', // Notes control
            '{62B0DF79-0464-470F-8AF7-4483CFEA0C7D}', // Address Map
            '{E7A81278-8635-4D9E-8D4D-59480B391C5B}', // Subgrid
        ];

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
                        /**
                         * @var $row \DOMNode
                         */
                        $cellCollection = [];

                        $cells = $formXPath->query( './cell', $row );
                        foreach ( $cells as $cell ) {
                            /**
                             * @var \DOMElement $cell
                             */
                            $cellId = $cell->getAttribute( 'id' );

                            $cellDefinition = [
                                'showLabel' => ( $cell->getAttribute( 'showlabel' ) === 'true' ),
                                'label' => $formXPath->evaluate( 'string(./labels/label/@description[1])', $cell ),
                                'colspan' => (int)$cell->getAttribute( 'colspan' ),
                                'rowspan' => (int)$cell->getAttribute( 'rowspan' ),
                                'isSpacer' => ( $cell->getAttribute( 'userspacer' ) === 'true' ),
                                'isVisible' => !$cell->hasAttribute( 'visible' )
                                               || ( $cell->hasAttribute( 'visible' ) && $cell->getAttribute( 'visible' ) !== 'false' ),
                            ];

                            $controlList = $formXPath->query( './control', $cell );
                            if ( $controlList->length ) {
                                $control = $controlList->item( 0 );

                                if ( in_array( strtoupper( $control->getAttribute( 'classid' ) ), $notSupportedControlClasses, true ) ) {
                                    continue;
                                }

                                $attrMd = null;
                                if ( isset( $metadata->attributes[$control->getAttribute( 'datafieldname' )] ) ) {
                                    $attrMd = $metadata->attributes[ $control->getAttribute( 'datafieldname' ) ];
                                }

                                $controlDefinition = [
                                    'id' => $control->getAttribute( 'id' ),
                                    'classId' => strtoupper( $control->getAttribute( 'classid' ) ),
                                    'disabled' => ( $control->getAttribute( 'disabled' ) === 'true' ),
                                    'required' => false,
                                    'name' => $control->getAttribute( 'datafieldname' ),
                                    'metadata' => $attrMd,
                                    'parameters' => [],
                                ];

                                if ( $controlDefinition['name'] === 'name' ) {
                                    $controlDefinition['name'] = '__compat_name';
                                }

                                if ( $cellDefinition['isVisible'] ) {
                                    $this->formControls[] = $controlDefinition['name'];
                                }

                                if ( $attrMd !== null ) {
                                    if ( array_key_exists( 'language', $this->attributes )
                                         && ( !array_key_exists( 'keep_labels', $this->attributes ) || $this->attributes['keep_labels'] !== true ) ) {
                                        $cellDefinition['label'] = $attrMd->getLabel( $this->attributes['language'] );
                                    }

                                    $controlDefinition['required'] = in_array( $attrMd->requiredLevel, [ 'ApplicationRequired', 'SystemRequired' ] );
                                }

                                if ( $controlDefinition['required'] && in_array( $controlDefinition['name'], $this->attributes['optional'] ) ) {
                                    $controlDefinition['required'] = false;
                                }
                                if ( !$controlDefinition['required'] && in_array( $controlDefinition['name'], $this->attributes['required'] ) ) {
                                    $controlDefinition['required'] = true;
                                }

                                $parameters = $formXPath->query( './parameters/*', $control );
                                foreach ( $parameters as $parameter ) {
                                    $controlDefinition['parameters'][$parameter->nodeName] = $parameter->nodeValue;
                                }

                                if ( array_key_exists( $controlDefinition['name'], $this->attributes['lookupviews'] ) ) {
                                    $controlDefinition['classId'] = static::CONTROL_LOOKUP_VIEW;
                                    $controlDefinition['options'] = $this->retrieveLookupView( $this->attributes['lookupviews'][$controlDefinition['name']] );
                                }

                                $cellDefinition['control'] = $controlDefinition;
                            }

                            $cellCollection[$cellId] = $cellDefinition;
                        }

                        if ( count( $cellCollection) ) {
                            $sectionDefinition['rows'][] = $cellCollection;
                        }
                    }

                    $columnDefinition['sections'][$sectionId] = $sectionDefinition;
                }

                $tabDefinition['columns'][] = $columnDefinition;
            }

            $formDefinition['tabs'][$tabId] = $tabDefinition;
        }

        /**
         * Provide default values for the form.
         *
         * Hidden default values are field-value pairs that don't have a corresponding control.
         */
        $hiddenDefaults = array_diff_key( $this->attributes['default'], array_flip( $this->formControls ) );
        $formDefinition['defaults'] = $this->attributes['default'];
        $formDefinition['hiddenDefaults'] = $hiddenDefaults;

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

            if ( $fieldValue === ''
                 || ( $fieldMetadata->isLookup && json_decode( $fieldValue ) === [ null, null, '' ] ) ) {
                $this->record->{$fieldName} = null;
                continue;
            }

            if ( $fieldMetadata->optionSet instanceof Entity\OptionSet ) {
                $this->record->{$fieldName} = (int)$fieldValue;
                continue;
            }

            if ( $fieldMetadata->isLookup ) {
                $reference = json_decode( $fieldValue, true );
                if ( !is_array( $reference ) || count( $reference ) !== 3 ) {
                    continue;
                }

                if ( !array_key_exists( 'LogicalName', $reference )
                     && !array_key_exists( 'Id', $reference )
                     && !array_key_exists( 'DisplayName', $reference ) ) {
                    continue;
                }

                if ( !$reference['LogicalName'] || !$reference['Id'] ) {
                    $this->record->{$fieldName} = null;
                    continue;
                }

                $entityReference = ASDK()->entity( $reference['LogicalName'] );
                $entityReference->ID = $reference['Id'];
                $entityReference->{$entityReference->metadata()->primaryNameAttribute} = $reference['DisplayName'];
                $this->record->{$fieldName} = $entityReference;

                continue;
            }

            if ( $fieldMetadata->type === 'DateTime' ) {
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

            $val = null;
            if ( isset( $fields[$fieldName] ) ) {
                $val = $fields[$fieldName];
            }
            $validationResult = $this->validateFieldValue( $fieldMetadata, $val );
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
     * Validates the given array of data only according to metadata.
     *
     * @param array $fields
     *
     * @return array
     */
    public function validateHeadless( $fields ) {
        $metadata = ACRM()->getMetadata()->getEntityDefinition( $this->entityName );

        $errors = [];
        foreach ( $fields as $fieldName => $fieldValue ) {
            if ( !array_key_exists( $fieldName, $metadata->attributes ) ) {
                continue;
            }

            $fieldMetadata = $metadata->attributes[$fieldName];

            $isRequired = in_array( $fieldMetadata->requiredLevel, [ 'ApplicationRequired', 'SystemRequired' ] ); // metadata based
            if ( $isRequired && in_array( $fieldName, $this->attributes['optional'] ) ) { // optional override
                $isRequired = false;
            }
            if ( !$isRequired && in_array( $fieldName, $this->attributes['required'] ) ) { // required override
                $isRequired = true;
            }

            if ( $isRequired && trim( $fields[$fieldName] ) === '' ) {
                $errors[$fieldName][] = __( 'The field is required.', 'integration-dynamics' );
                continue;
            }

            $validationResult = $this->validateFieldValue( $fieldMetadata, $fieldValue );
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
     * Dispatches the submitted form and saves data to the CRM.
     *
     * @return array
     */
    public function dispatch() {
        $request = ACRM()->request->request;
        $fields = $request->all();

        if ( ACRM()->request->getMethod() !== 'POST' ) {
            return [ 'submission' => false, 'fields' => $fields ];
        }

        if ( isset( $fields['__compat_name'] ) ) {
            $fields['name'] = $fields['__compat_name'];
        }

        $dispatchedForm = clone $this;

        /*
         * The `key` allows to distinguish submissions to different forms on one page.
         * The key is calculated automatically based on form arguments (not the template, if it's a custom form).
         * If two identical forms are on one page, the key would be identical too. It is possible
         * to override the key with the `key` attribute.
         */
        $submittedKey = $request->get( '_key' );

        /*
         * If the key is absent, we're likely dealing with old custom forms. Continue without the key.
         * Otherwise match the incoming key to the model and halt as if no submission is taking place.
         */
        if ( $submittedKey !== null && $submittedKey !== $dispatchedForm->attributes['key'] ) {
            return [ 'submission' => false, 'fields' => $fields ];
        }

        $validateMethod = [ $dispatchedForm, 'validate' ];
        if ( !$this->formName ) {
            $validateMethod = [ $dispatchedForm, 'validateHeadless' ];
        }

        $validationResult = $validateMethod( $fields );

        /**
         * Filters the default form validation.
         *
         * @param array $validationResult
         * @param array fields Map of fields received from the form.
         * @param Model $dispatchedForm
         */
        $validationResult = apply_filters( 'wpcrm/twig/form/validate', $validationResult, $fields, $dispatchedForm );

        if ( $validationResult['status'] ) {
            $record = $dispatchedForm->hydrateRecord( $validationResult['payload'] );
            $mode = $redirectAction = $dispatchedForm->attributes['mode'];
            try {
                if ( $mode === 'create' ) {
                    ASDK()->create( $record );
                    $fields = [];
                } elseif ( $mode === 'edit' ) {
                    ASDK()->update( $record );
                } elseif ( $mode === 'upsert' ) {
                    $response = ASDK()->upsert( $record );

                    // Toolkit should update the ID itself, but it doesn't. Fix it.
                    $record->ID = str_replace( $record->logicalName, '', $response->Target );
                }

                /**
                 * Allows post-submit actions on Twig forms.
                 *
                 * @param \AlexaCRM\WordpressCRM\Form\Model $dispatchedForm
                 * @param \AlexaCRM\CRMToolkit\Entity $record
                 */
                do_action( 'wordpresscrm_twig_form_submit_success', $dispatchedForm, $record );

                $redirectUrl = static::getActionRedirect( $redirectAction, $dispatchedForm );
                if ( is_string( $redirectUrl ) && $redirectUrl !== '' ) {
                    wordpresscrm_javascript_redirect( $redirectUrl );
                }

                return [ 'submission' => true, 'status' => true, 'fields' => $fields ];
            } catch ( \Exception $e ) {
                $error = [ 'Error' => [ $e->getMessage() ] ];
                return [ 'submission' => true, 'status' => false, 'fields' => $fields, 'errors' => $error ];
            }
        }

        return [ 'submission'=> true, 'status' => false, 'fields' => $fields, 'errors' => $validationResult['payload'] ];
    }

    /**
     * Returns a suitable redirect URL for the given action.
     *
     * @param string $action create/edit/upsert
     * @param Model $model
     *
     * @return string Empty string returned if no redirect is required.
     */
    private static function getActionRedirect( $action, Model $model ) {
        $redirectUrl = null;

        if ( array_key_exists( $action, $model->attributes['redirect'] ) ) {
            $redirectUrl = $model->attributes['redirect'][$action];
        }

        if ( array_key_exists( 'always', $model->attributes['redirect'] ) ) {
            $redirectUrl = $model->attributes['redirect']['always'];
        }

        $recordId = ( $model->record !== null )? $model->record->ID : null;
        $effectiveRedirectUrl = trim( sprintf( $redirectUrl, $recordId ) );

        return apply_filters( 'wpcrm/twig/form/redirect', $effectiveRedirectUrl, $action, $model );
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
        if ( !$this->formName ) {
            return null;
        }

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

    /**
     * Retrieve records according to the given view.
     *
     * @param array $viewDefinition [ entityName, viewName ]
     *
     * @return Entity[]
     */
    private function retrieveLookupView( $viewDefinition ) {
        if ( !is_array( $viewDefinition ) || count( $viewDefinition ) !== 2 ) {
            return [];
        }

        $view = View::getViewForEntity( $viewDefinition[0], $viewDefinition[1] );
        if ( $view === null ) {
            return [];
        }

        $dataCacheKey = 'wpcrm_data_' . sha1( $view->fetchxml );
        $options = ACRM()->getCache()->get( $dataCacheKey );
        if ( $options == null ) {
            $options = ASDK()->retrieveMultiple( $view->fetchxml );
            ACRM()->getCache()->set( $dataCacheKey, $options, 2 * 60 * 60 * 24 );
        }

        return $options->Entities;
    }

    /**
     * Prevent augmenting parent's objects.
     */
    public function __clone() {
        $this->record = clone $this->record;
    }

}
