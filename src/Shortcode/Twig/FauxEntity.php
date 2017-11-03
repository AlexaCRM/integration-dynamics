<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Twig;

use AlexaCRM\CRMToolkit\Client;
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
        if ( !ACRM()->connected() ) {
            return null;
        }

        return ACRM()->getSdk()->entity( $this->entityName, $recordId );
    }

    /**
     * @param string $recordId
     *
     * @return bool
     * @see FauxEntity::offsetExists()
     */
    public function __isset( $recordId ) {
        return $this->offsetExists( $recordId );
    }

    /**
     * Dynamic properties must be true to be surfaced in Twig templates,
     * and checking record for existence before fetching it is not cost-efficient,
     * thus the method always returns true if the $recordId is a valid GUID.
     *
     * @param string $recordId Entity record ID.
     *
     * @return boolean
     */
    public function offsetExists( $recordId ) {
        return Client::isGuid( $recordId );
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
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet( $offset, $value ) {
        return;
    }

    /**
     * Object is read-only.
     *
     * @param mixed $offset
     */
    public function offsetUnset( $offset ) {
        return;
    }
}
