<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Twig;

use AlexaCRM\CRMToolkit\Entity\Metadata;

/**
 * Provides access to metadata in Twig templates.
 */
class MetadataCollection implements \ArrayAccess {

    /**
     * @param $entityName
     *
     * @return Metadata
     */
    public function __get( $entityName ) {
        return $this->offsetGet( $entityName );
    }

    /**
     * @param $entityName
     *
     * @return bool
     */
    public function __isset( $entityName ) {
        return $this->offsetExists( $entityName );
    }

    /**
     * @param string $entityName
     *
     * @return bool
     */
    public function offsetExists( $entityName ) {
        if ( !ACRM()->connected() ) {
            return false;
        }

        $entityName = strtolower( $entityName );
        $entities = ACRM()->getMetadata()->getEntitiesList();

        return array_key_exists( $entityName, $entities );
    }

    /**
     * @param string $entityName
     *
     * @return Metadata
     */
    public function offsetGet( $entityName ) {
        if ( !ACRM()->connected() ) {
            return null;
        }

        $entityName = strtolower( $entityName );

        return ACRM()->getMetadata()->getEntityDefinition( $entityName );
    }

    /**
     * Void.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet( $offset, $value ) {
        return;
    }

    /**
     * Void.
     *
     * @param mixed $offset
     */
    public function offsetUnset( $offset ) {
        return;
    }
}
