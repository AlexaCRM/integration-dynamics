<?php

namespace AlexaCRM\WordpressCRM\Admin\Metabox\ShortcodeWizard;

/**
 * Shortcode field definition consumed by the ShortcodeWizard.
 *
 * @package AlexaCRM\WordpressCRM\Admin\Metabox\ShortcodeWizard
 */
class Field {

    /**
     * Field type.
     */
    const TYPE = 'text';

    /**
     * Field name.
     *
     * @var string
     */
    public $name;

    /**
     * Human-readable field name.
     *
     * @var string
     */
    public $displayName;

    /**
     * Field description.
     *
     * @var string
     */
    public $description;

    /**
     * List of fields (names) that this field's value depends on.
     *
     * @var array
     */
    public $bindingFields = [];

    /**
     * @var \Closure
     */
    protected $valueGenerator;

    /**
     * @var \Closure
     */
    protected $staticValueGenerator;

    /**
     * Field constructor.
     *
     * @param string $name
     * @param string $displayName
     */
    public function __construct( $name = '', $displayName = '' ) {
        $this->name = $name;
        $this->displayName = $displayName;
    }

    /**
     * @param array $args Optional. A list of values that the field can depend on.
     *
     * @return mixed
     */
    public function getValue( $args = [] ) {
        switch ( true ) {
            case $this->isStaticValueAvailable():
                $generator = $this->staticValueGenerator;
                break;
            case $this->isApiAvailable():
                $generator = $this->valueGenerator;
                break;
            default:
                return null;
        }

        return $generator( $args );
    }

    /**
     * Sets the value generator for this field.
     *
     * @param \Closure $generator
     *
     * @return $this
     */
    public function setValueGenerator( \Closure $generator ) {
        $this->valueGenerator = $generator;

        return $this;
    }

    /**
     * Sets the static value generator for this field.
     *
     * Static values are supplied to the front-end during field initialization and are not requested via API.
     *
     * @param \Closure $generator
     *
     * @return $this
     */
    public function setStaticValueGenerator( \Closure $generator ) {
        $this->staticValueGenerator = $generator;

        return $this;
    }

    /**
     * Tells whether this field can retrieve default or possible values via API.
     *
     * @return bool
     */
    public function isApiAvailable() {
        return ( $this->valueGenerator instanceof \Closure );
    }

    /**
     * Tells whether this field has a static default value or possible values.
     *
     * @return bool
     */
    public function isStaticValueAvailable() {
        return ( $this->staticValueGenerator instanceof \Closure );
    }

}
