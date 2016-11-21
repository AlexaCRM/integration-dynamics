<?php

namespace AlexaCRM\WordpressCRM\Admin\Metabox\ShortcodeWizard;

use AlexaCRM\WordpressCRM\Admin\Metabox\ShortcodeWizard\Field\Hidden;
use AlexaCRM\WordpressCRM\Plugin;

/**
 * Shortcode definition consumed by the Shortcode Wizard.
 *
 * @package AlexaCRM\WordpressCRM\Admin\Metabox\ShortcodeWizard
 */
class Shortcode {

    /**
     * Internal unprefixed shortcode name.
     *
     * @var string
     */
    public $name = '';

    /**
     * Human-readable shortcode name.
     *
     * @var string
     */
    public $displayName = '';

    /**
     * Shortcode description.
     *
     * @var string
     */
    public $description = '';

    /**
     * Collection of shortcode fields (attributes).
     *
     * @var Field[]
     */
    protected $fields = [];

    /**
     * Shortcode constructor.
     *
     * @param string $name
     * @param string $displayName
     */
    public function __construct( $name = '', $displayName = '' ) {
        $this->name = $name;
        $this->displayName = $displayName;
    }

    /**
     * Adds a field to the collection of fields.
     *
     * If the field has been registered already, it is overwritten with the new object reference.
     *
     * @param Field $field
     *
     * @return $this
     */
    public function registerField( Field $field ) {
        $this->fields[$field->name] = $field;

        return $this;
    }

    /**
     * Returns a shortcode field definition.
     *
     * @param string $fieldName
     *
     * @return Field
     */
    public function getField( $fieldName ) {
        if ( !array_key_exists( $fieldName, $this->fields ) ) {
            throw new \InvalidArgumentException( "Shortcode field [{$fieldName}] is not registered" );
        }

        return $this->fields[$fieldName];
    }

    /**
     * Returns the collection of shortcode fields.
     *
     * @return Field[]
     */
    public function getFields() {
        return $this->fields;
    }

    /**
     * Generates the shortcode with given values.
     *
     * @param array $fieldValues List of field values ( fieldName => fieldValue ).
     *
     * @return string
     */
    public function generateCode( $fieldValues ) {
        $shortcodePrefix = Plugin::PREFIX;

        /*
         * %1$s - shortcode prefix
         * %2$s - shortcode name
         * %2$s - shortcode arguments
         */
        $shortcodeTemplate = '[%1$s%2$s %3$s]';

        // filter empty fields
        $fieldValues = array_filter( $fieldValues );

        // filter hidden fields
        $allowedFieldNames = array_filter( array_keys( $fieldValues ), function( $fieldName ) {
            return !( $this->getField( $fieldName ) instanceof Hidden );
        } );
        $fieldValues = array_intersect_key( $fieldValues, array_flip( $allowedFieldNames ) );

        $shortcodeArguments = $this->_arrayToAttributes( $fieldValues );

        return sprintf( $shortcodeTemplate, $shortcodePrefix, $this->name, $shortcodeArguments );
    }

    /**
     *
     * @param $fieldName
     * @param array $args
     *
     * @return mixed
     */
    public function getFieldValue( $fieldName, $args = [] ) {
        $field = $this->getField( $fieldName );

        return $field->getValue( $args );
    }

    /**
     * Converts the associative array to a white-space-separated list of HTML-like attributes.
     *
     * @param array $attributes Collection of key-value pairs ( key => value ).
     *
     * @return string A string like 'key1="value1" key2="value2"'.
     */
    protected function _arrayToAttributes( $attributes ) {
        $formattedAttributes = array_map( function( $attributeName ) use ( $attributes ) {
            return $attributeName . '="' . esc_attr( $attributes[$attributeName] ) . '"';
        }, array_keys( $attributes ) );

        return join( ' ', $formattedAttributes );
    }

}
