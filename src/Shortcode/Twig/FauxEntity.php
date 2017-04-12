<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Twig;

use AlexaCRM\CRMToolkit\Entity;

/**
 * Implements entity record fetcher for Twig templates.
 *
 * @package AlexaCRM\WordpressCRM\Shortcode\Inline
 */
class FauxEntity implements \ArrayAccess {

    /**
     * Entity name.
     *
     * @var string
     */
    public $entityName;

    /**
     * FauxEntity constructor.
     *
     * @param string $entityName
     */
    public function __construct( $entityName ) {
        $this->entityName = $entityName;
    }

    /**
     * Retrieves an entity record from the CRM.
     *
     * @param string $recordId Entity record ID.
     *
     * @return \AlexaCRM\CRMToolkit\Entity
     */
    public function __get( $recordId ) {
        return ASDK()->entity( $this->entityName, $recordId );
    }

    /**
     * Dynamic properties must be true to be surfaced in Twig templates,
     * and checking record for existence before fetching it is not cost-efficient,
     * thus the method always returns true.
     *
     * @param string $recordId
     *
     * @return bool
     */
    public function __isset( $recordId ) {
        return true;
    }

    /**
     * @param string $recordId Entity record ID.
     *
     * @return boolean
     * @see FauxEntity::__isset()
     */
    public function offsetExists( $recordId ) {
        return true;
    }

    /**
     * @param mixed $recordId
     *
     * @return Entity
     * @see FauxEntity::__get()
     */
    public function offsetGet( $recordId ) {
        return $this->{$recordId};
    }

    /**
     * Object is read-only.
     */
    public function offsetSet( $offset, $value ) {
        return;
    }

    /**
     * Object is read-only.
     */
    public function offsetUnset( $offset ) {
        return;
    }
}
