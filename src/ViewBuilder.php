<?php

namespace AlexaCRM\WordpressCRM;

use AlexaCRM\CRMToolkit\Client;

/**
 * Builds entityview objects for the Twig environment.
 */
class ViewBuilder {

    /**
     * @var string
     */
    public $entityName;

    /**
     * @var string
     */
    public $viewName;

    /**
     * @var array
     */
    public $attributes;

    /**
     * @var bool
     */
    private $isCacheEnabled = false;

    /**
     * ViewBuilder constructor.
     *
     * @param array $attributes
     */
    public function __construct( $attributes ) {
        $this->entityName = $attributes['entity'];
        $this->viewName = $attributes['name'];

        unset( $attributes['entity'], $attributes['name'] );
        $this->attributes = $attributes;

        if ( array_key_exists( 'cache', $attributes ) ) {
            $this->isCacheEnabled = true;
        }
    }

    /**
     * Builds the entityview object for the Twig environment.
     *
     * @return array
     * @throws \Exception
     *
     * @see https://community.adxstudio.com/products/adxstudio-portals/documentation/configuration-guide/liquid-templates/objects/entityview/
     */
    public function build() {
        $view = View::getViewForEntity( $this->entityName, $this->viewName );

        if ( $view === null ) {
            throw new \Exception( 'Specified view not found' );
        }

        list ( $fetchXML, $layoutXML ) = [ $view->fetchxml, $view->layoutxml ];

        // Substitute parameters
        $fetchXML = $this->processParameters( $fetchXML );

        // Substitute lookups
        $fetchXML = $this->processLookups( $fetchXML );

        $isPaged = array_key_exists( 'count', $this->attributes );
        $perPage = Client::MAX_CRM_RECORDS;
        $currentPage = 1;
        if ( $isPaged ) {
            $perPage = (int)$this->attributes['count'];
            if ( !$perPage ) { // div by zero mitigation
                $perPage = 10;
            }

            $currentPage = ACRM()->request->query->getInt( 'viewPage', 1 );
            $fetchXML = str_replace( '<fetch ', '<fetch returntotalrecordcount="true" count="' . $perPage . '" page="' . $currentPage . '" ', $fetchXML );
        }

        $retrieveResult = $this->retrieveData( $fetchXML );
        $totalPages = (int)ceil( $retrieveResult->TotalRecordCount / $perPage );

        $languageCode = array_key_exists( 'language', $this->attributes )? $this->attributes['language'] : null;
        $columns = $this->getColumns( $layoutXML, $languageCode );
        $viewCells = array_map( function( $column ) {
            return [ 'name' => $column['logical_name'] ];
        }, $columns );

        $entitiesHash = [];
        foreach ( $retrieveResult->Entities as $record ) {
            $entitiesHash[$record->id] = $record;
        }

        $listView = [
            'columns' => $columns,
            'entity_logical_name' => $this->entityName,
            'first_page' => $retrieveResult->Count? 1 : null,
            'last_page' => $retrieveResult->Count? $totalPages : null,
            'name' => $this->viewName,
            'next_page' => ( $isPaged && $currentPage < $totalPages )? $currentPage + 1 : null,
            'page' => $currentPage,
            'pages' => $isPaged? range( 1, $totalPages ) : [ 1 ],
            'page_size' => $perPage,
            'previous_page' => ( $isPaged && $currentPage > 1 )? $currentPage - 1 : null,
            'primary_key_logical_name' => ACRM()->getMetadata()->getEntityDefinition( $this->entityName )->primaryIdAttribute,
            'records' => $entitiesHash,
            'rows' => View::getViewRows( $retrieveResult, $viewCells, $fetchXML ),
            'total_pages' => $totalPages,
            'total_records' => $retrieveResult->TotalRecordCount,
        ];

        return $listView;
    }

    /**
     * Replaces FetchXML placeholders "{int}" with specified parameters.
     *
     * @param string $fetchXML
     *
     * @return string
     */
    private function processParameters( $fetchXML ) {
        if ( !array_key_exists( 'parameters', $this->attributes ) ) {
            return $fetchXML;
        }

        $parameters = $this->attributes['parameters'];

        if ( is_string( $parameters ) ) {
            return FetchXML::replacePlaceholderValuesByParametersArray( $fetchXML, $parameters );
        }

        if ( !is_array( $parameters ) ) {
            return $fetchXML;
        }

        foreach ( $parameters as $i => $param ) {
            $placeholder = '{' . $i . '}';
            $fetchXML = FetchXML::replaceConditionPlaceholderByValue( $fetchXML, $placeholder, $param );
        }

        return $fetchXML;
    }

    /**
     * Replaces FetchXML conditions with specified lookup values.
     *
     * @param string $fetchXML
     *
     * @return string
     */
    private function processLookups( $fetchXML ) {
        if ( !isset( $this->attributes['lookups'] ) || !is_array( $this->attributes['lookups'] ) ) {
            return $fetchXML;
        }

        $lookups = $this->attributes['lookups'];
        foreach ( $lookups as $field => $value ) {
            $fetchXML = FetchXML::replaceCondition( $fetchXML, 'attribute', $field, $value );
        }

        return $fetchXML;
    }

    /**
     * Retrieves data from the CRM with transparent data caching.
     *
     * @param string $fetchXML
     *
     * @return null|\stdClass
     */
    private function retrieveData( $fetchXML ) {
        $cache = ACRM()->getCache();
        $cacheKey = 'wpcrm_twigdata_' . sha1( $fetchXML );

        if ( $this->isCacheEnabled ) {
            $retrieveResult = $cache->get( $cacheKey );

            if ( $retrieveResult !== null ) {
                return $retrieveResult;
            }
        }

        $client = ACRM()->getSdk();
        if ( !$client ) {
            return null;
        }

        $retrieveResult = $client->retrieveMultiple( $fetchXML );

        if ( $this->isCacheEnabled ) {
            try {
                $interval = new \DateInterval( $this->attributes['cache'] );
                $cacheTime = $seconds = $interval->y * YEAR_IN_SECONDS
                                        + $interval->m * MONTH_IN_SECONDS
                                        + $interval->d * DAY_IN_SECONDS
                                        + $interval->h * HOUR_IN_SECONDS
                                        + $interval->i * MINUTE_IN_SECONDS
                                        + $interval->s;

                $cache->set( $cacheKey, $retrieveResult, $cacheTime );
            } catch ( \Exception $e ) {
                ACRM()->getLogger()->warn( 'Incorrect cache interval given in the view tag', [ 'attributes' => $this->attributes ] );

                return $retrieveResult;
            }
        }

        return $retrieveResult;
    }

    /**
     * Returns column descriptions for the view.
     *
     * @param string $layoutXML
     * @param int $languageCode
     *
     * @return array
     * @see https://community.adxstudio.com/products/adxstudio-portals/documentation/configuration-guide/liquid-templates/objects/entitylist/#entitylistviewcolumn
     */
    private function getColumns( $layoutXML, $languageCode = null ) {
        $viewLayout = new \SimpleXMLElement( $layoutXML );
        $rawCells = $viewLayout->xpath( './/cell' );

        $columns = [];
        $entityAttributes = ACRM()->getMetadata()->getEntityDefinition( $this->entityName )->attributes;
        foreach ( $rawCells as $cell ) {
            $columnName = (string)$cell['name'];

            $columns[$columnName] = [
                'attribute_type' => $entityAttributes[$columnName]->type,
                'logical_name' => $columnName,
                'name' => $entityAttributes[$columnName]->label,
                'width' => (int)$cell['width'],
            ];
            if ( $languageCode ) {
                $columns[$columnName]['name'] = $entityAttributes[$columnName]->getLabel($languageCode);
            }
        }

        return $columns;
    }

}
