<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Twig;

/**
 * Implements pseudo entity collection for Twig templates.
 *
 * @package AlexaCRM\WordpressCRM\Shortcode\Inline
 */
class FauxEntitiesCollection {

    /**
     * Provides access to the entity fetcher.
     *
     * @param string $entityName
     *
     * @return FauxEntity
     */
    public function __get( $entityName ) {
        return new FauxEntity( $entityName );
    }

    /**
     * Checks whether given entity exists.
     *
     * @param string $entityName
     *
     * @return bool
     */
    public function __isset( $entityName ) {
        $metadata = ACRM()->getMetadata();

        return ( ACRM()->connected() && array_key_exists( $entityName, $metadata->getEntitiesList() ) );
    }
}
