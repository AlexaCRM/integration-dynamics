<?php

// Exit if accessed directly
namespace AlexaCRM\WordpressCRM;

use AlexaCRM\CRMToolkit\AbstractClient;
use AlexaCRM\CRMToolkit\Entity;
use AlexaCRM\CRMToolkit\Entity\EntityReference;
use AlexaCRM\WordpressCRM\Shortcode\Field;
use DOMDocument;
use Exception;
use SimpleXMLElement;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Implements the Lookup Dialog.
 *
 * @package AlexaCRM\WordpressCRM
 */
class LookupDialog {

    /**
     * Records per page in the Lookup Dialog.
     *
     * @const int
     */
    const PER_PAGE = 10;

    /**
     * Lookup constructor.
     */
    public function __construct() {
        add_action( 'wp_ajax_retrieve_lookup_request', [ $this, 'retrieve' ] );
        add_action( 'wp_ajax_search_lookup_request', [ $this, 'search' ] );
        add_action( 'wp_ajax_nopriv_retrieve_lookup_request', [ $this, 'retrieve' ] );
        add_action( 'wp_ajax_nopriv_search_lookup_request', [ $this, 'search' ] );
    }

    /**
     * Creates a response for the lookup request.
     */
    public function retrieve() {
        $query = ACRM()->request->query;

        $lookupType = $query->get( 'lookupType' );

        $pagingCookie = urldecode( $query->get( 'pagingCookie', '' ) );
        if ( strlen( $pagingCookie ) < 3 ) {
            $pagingCookie = null;
        }

        $pagingNumber = $query->getInt( 'pageNumber' );
        if ( !$pagingNumber ) {
            $pagingNumber = null;
        }

        $entity = ASDK()->entity( $lookupType );

        $returnedTypeCode = $entity->metadata()->objectTypeCode;
        $primaryNameAttr = $entity->metadata()->primaryNameAttribute;
        $lookup = $this->retrieveLookupView( $returnedTypeCode, 64, $primaryNameAttr );

        $records = ASDK()->retrieveMultiple( $lookup['fetchxml'], false, $pagingCookie, self::PER_PAGE, $pagingNumber );

        $noRecordsMessage = '<table class="crm-popup-no-results"><tr><td align="center" style="vertical-align: middle">'
                            . __( 'No records are available in this view.', 'integration-dynamics' )
                            . '</td></tr></table>';

        if ( !$records || $records->Count < 1 ) {
            wp_die( $noRecordsMessage );
        }

        $pagingCookie = null;
        if ( $records->MoreRecords && $records->PagingCookie != null ) {
            $pagingCookie = urlencode( $records->PagingCookie );
        }

        $fetchDom = new DOMDocument();
        $fetchDom->loadXML( $lookup['fetchxml'] );

        $layout = new SimpleXMLElement( $lookup['layoutxml'] );

        $cells = $layout->xpath( ".//cell" );

        $output = '<table class="lookup-table">'
            . $this->renderTableHeader( $cells, $fetchDom, $records->Entities )
            . '<tbody>'
            . $this->renderTableResults( $cells, $fetchDom, $records->Entities )
            . '</tbody></table>';

        $response = [
            'data' => $output,
            'pagingcookie' => $pagingCookie,
            'morerecords' => ( $records->MoreRecords ) ? '1' : '0',
        ];

        wp_send_json( $response );
    }

    /**
     * Creates a response for the lookup request with search.
     */
    public function search() {
        $query = ACRM()->request->query;

        $lookupType = $query->get( 'lookupType' );
        $entity = ASDK()->entity( $lookupType );

        $returnedTypeCode = $entity->metadata()->objectTypeCode;
        $primaryNameAttr = $entity->metadata()->primaryNameAttribute;

        $searchString = urldecode( $query->get( 'searchstring' ) );

        if ( $searchString != "" ) {
            $searchView = $this->retrieveLookupView( $returnedTypeCode, 4, $primaryNameAttr );

            $fetchXML   = new SimpleXMLElement( $searchView['fetchxml'] );
            $conditions = $fetchXML->xpath( './/condition[@value]' );

            $searchXML = $fetchXML->asXML();

            foreach ( $conditions as $condition ) {
                $attribute = (string) $condition["attribute"][0];
                $value     = (string) $condition["value"][0];

                $oldCondition = $condition[0]->asXML();
                $newCondition = $condition;

                if ( $value == "{0}" ) {
                    $newCondition[0]["value"] = "%{$searchString}%";
                    $newCondition[0]["operator"] = 'like';

                    if ( $entity->attributes[ $attribute ]->isLookup ) {
                        $newCondition[0]["value"] = AbstractClient::EmptyGUID;

                        if ( AbstractClient::isGuid( $searchString ) ) {
                            $newCondition[0]["value"] = $searchString;
                        }
                    }

                    $searchXML = str_replace( $oldCondition, $newCondition[0]->asXML(), $searchXML );
                }
            }

            $records = ASDK()->retrieveMultiple( $searchXML );
        } else {
            $records = ASDK()->retrieveMultipleEntities( $lookupType );
        }

        $lookup = $this->retrieveLookupView( $returnedTypeCode, 64, $primaryNameAttr );

        $noRecordsMessage = '<table class="crm-popup-no-results"><tr><td align="center" style="vertical-align: middle">'
                            . __( 'No records are available in this view.', 'integration-dynamics' )
                            . '</td></tr></table>';

        if ( !$records || $records->Count < 1 ) {
            wp_die( $noRecordsMessage );
        }

        $fetchDom = new DOMDocument();
        $fetchDom->loadXML( $lookup['fetchxml'] );

        $layout = new SimpleXMLElement( $lookup['layoutxml'] );

        $cells = $layout->xpath( ".//cell" );

        $output = '<table class="lookup-table">'
                  . $this->renderTableHeader( $cells, $fetchDom, $records->Entities )
                  . '<tbody>'
                  . $this->renderTableResults( $cells, $fetchDom, $records->Entities )
                  . '</tbody></table>';

        wp_die( $output );
    }

    /**
     * Retrieves the lookup view for given entity and type
     *
     * @param int $returnedTypeCode EntityMetadata.ObjectTypeCode.
     *                              See https://msdn.microsoft.com/en-us/library/microsoft.xrm.sdk.metadata.entitymetadata.objecttypecode.aspx
     * @param int $queryType See https://msdn.microsoft.com/en-us/library/gg309339.aspx
     * @param string $sortableAttributeName Attribute name to sort by
     *
     * @return array
     * @throws Exception
     */
    private function retrieveLookupView( $returnedTypeCode, $queryType = 64, $sortableAttributeName = 'name' ) {
        $cacheKey = 'wpcrm_lookup_' . sha1( 'querytype_' . $queryType . '_returnedtypecode_' . $returnedTypeCode );
        $cache = ACRM()->getCache();

        if ( $cache->exists( $cacheKey ) ) {
            return $cache->get( $cacheKey );
        }

        $fetchView = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
                                <entity name="savedquery">
                                  <all-attributes  />
                                  <filter type="and">
                                    <condition attribute="querytype" operator="eq" value="' . $queryType . '" />'
                     . ( ( $queryType === 4 )? '<condition attribute="isquickfindquery" operator="eq" value="true" />' : '' )
                                    . '<condition attribute="returnedtypecode" operator="eq" value="' . $returnedTypeCode . '" />
                                  </filter>
                                </entity>
                              </fetch>';

        $lookupView = ASDK()->retrieveSingle( $fetchView );

        if ( $lookupView == null ) {
            throw new Exception( 'Unable to retrieve specified SavedQuery' );
        }

        $lookup = [
            'fetchxml' => $lookupView->fetchxml,
        ];

        if ( $lookupView->layoutxml ) {
            $lookup['layoutxml'] = $lookupView->layoutxml;
        }

        $fetchDOM = new DOMDocument();
        $fetchDOM->loadXML( $lookup['fetchxml'] );

        // add order for the first attribute in the FetchXML in case no order is specified
        if ( !$fetchDOM->getElementsByTagName( 'order' )->length ) {
            $orderElement = $fetchDOM->getElementsByTagName( 'entity' )->item( 0 )
                ->appendChild( $fetchDOM->createElement( 'order' ) );
            $orderElement->setAttribute( 'attribute', $sortableAttributeName );
            $orderElement->setAttribute( 'descending', 'false' );

            $lookup['fetchxml'] = $fetchDOM->saveXML( $fetchDOM->firstChild );
        }

        $cache->set( $cacheKey, $lookup, 2* 60 * 60 * 24 );

        return $lookup;
    }

    /**
     * @param $cells
     * @param DOMDocument $fetchDom
     * @param Entity[] $records
     *
     * @return string
     */
    private function renderTableHeader( $cells, $fetchDom, $records ) {
        $result = '<thead><tr><th></th>';

        foreach ( $cells as $cell ) {
            $result .= '<th><span>';

            if ( strpos( (string) $cell["name"], "." ) ) {
                $parts = explode( ".", (string) $cell["name"] );

                $linkedEntities = $fetchDom->getElementsByTagName( 'link-entity' );

                $linkedEntityName = null;

                for ( $i = 0; $i < $linkedEntities->length; $i ++ ) {
                    if ( $parts[0] == $linkedEntities->item( $i )->attributes->getNamedItem( 'alias' )->value ) {
                        $linkedEntityName = $linkedEntities->item( $i )->attributes->getNamedItem( 'to' )->value;
                    }
                }

                if ( $linkedEntityName != null ) {
                    $linkedRecord = $records[0]->{$linkedEntityName};
                    if ( $linkedRecord instanceof EntityReference ) {
                        $linkedRecord = ASDK()->entity( $linkedRecord->logicalName, $linkedRecord->id );
                    }

                    if ( $linkedRecord instanceof Entity ) {
                        $result .= $linkedRecord->getPropertyLabel( $parts[1] );
                    }
                }
            } else {
                $result .= $records[0]->getPropertyLabel( (string) $cell["name"] );
            }

            $result .= '</span></th>';
        }

        $result .= '</tr></thead>';

        return $result;
    }

    /**
     * @param $cells
     * @param DOMDocument $fetchDom
     * @param Entity[] $records
     *
     * @return string
     */
    private function renderTableResults( $cells, $fetchDom, $records ) {
        $result = '';

        foreach ( $records as $record ) {
            $result .= '<tr class="body-row" data-entityid="' . esc_attr( $record->ID ) . '"  data-name="' . esc_attr( $record->displayname ) . '"><td><div class="lookup-checkbox"></div></td>';
            foreach ( $cells as $cell ) {
                $result .= '<td>';

                $cellName = (string)$cell['name'];

                if ( strpos( $cellName, "." ) ) {
                    $parts = explode( ".", $cellName );

                    $linkedRecord = $record->{$parts[0]};
                    if ( is_null( $linkedRecord ) ) {
                        /*
                         * Related entity not found. Perhaps the link-entity wasn't present in the search FetchXML.
                         * Here we look into the Lookup FetchXML to find out via what field the entity is linked
                         * using the alias.
                         */
                        $xpath = new \DOMXPath( $fetchDom );
                        $query = $xpath->query( "//link-entity[@alias='{$parts[0]}']" );
                        if ( $query->length ) {
                            $resolvedRelationName = $query->item( 0 )->getAttribute( 'from' );
                            $linkedRecord = $record->{$resolvedRelationName};
                        }
                    }

                    if ( !( $linkedRecord instanceof Entity ) && $linkedRecord instanceof EntityReference ) {
                        $linkedRecord = ASDK()->entity( $linkedRecord->logicalName, $linkedRecord->id );
                    }

                    if ( $linkedRecord instanceof Entity ) {
                        $result .= $linkedRecord->getFormattedValue( $parts[1] );
                    }
                } else {
                    $formattedCellValue = $record->getFormattedValue( $cellName );

                    $cellValue = $formattedCellValue;

                    if ( ( $record->{$cellName} ) instanceof EntityReference
                         && ( $url = ACRM()->getBinding()->buildUrl( $record->{$cellName} ) )
                    ) {
                        $cellValue = '<a href="' . esc_attr( $url ) . '">' . $formattedCellValue . '</a>';
                    }

                    $result .= $cellValue;
                }

                $result .= '</td>';
            }

            $result .= '</tr>';
        }

        return $result;
    }
}
