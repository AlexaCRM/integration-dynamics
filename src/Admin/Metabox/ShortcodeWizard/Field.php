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
        if ( !( $this->valueGenerator instanceof \Closure ) ) {
            return '';
        }

        $generator = $this->valueGenerator;

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

}
