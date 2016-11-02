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
class Lookup {

    /**
     * Lookup constructor.
     */
    public function __construct() {
        add_action( 'wp_ajax_retrieve_lookup_request', array( &$this, 'retrieve_lookup_request' ) );
        add_action( 'wp_ajax_search_lookup_request', array( &$this, 'search_lookup_request' ) );
        add_action( 'wp_ajax_nopriv_retrieve_lookup_request', array( &$this, 'retrieve_lookup_request' ) );
        add_action( 'wp_ajax_nopriv_search_lookup_request', array( &$this, 'search_lookup_request' ) );
    }

    /**
     * Creates a response for the lookup request.
     */
    public function retrieve_lookup_request() {
        if ( !isset( $_REQUEST ) ) {
            die();
        }

        $lookupType = $_REQUEST['lookupType'];
        if ( !$lookupType ) {
            die();
        }

        $pagingCookie = null;

        if ( isset( $_REQUEST['pagingCookie'] ) && ( strlen( $_REQUEST['pagingCookie'] ) > 2 ) ) {
            $pagingCookie = urldecode( $_REQUEST['pagingCookie'] );
        }

        $pagingNumber = null;

        if ( isset( $_REQUEST['pageNumber'] ) && ( strlen( $_REQUEST['pageNumber'] ) > 0 ) ) {
            $pagingNumber = $_REQUEST['pageNumber'];
        }

        $entity = ASDK()->entity( $lookupType );

        $returnedTypeCode = $entity->metadata()->objectTypeCode;
        $lookup = $this->retrieveLookupView( $returnedTypeCode, 64, $entity->metadata()->primaryNameAttribute );

        $fetchDom = new DOMDocument();
        $fetchDom->loadXML( $lookup['fetchxml'] );

        $invoices = ASDK()->retrieveMultiple( $lookup['fetchxml'], false, $pagingCookie, 50, $pagingNumber );

        $noRecordsMessage = '<table class="crm-popup-no-results"><tr><td align="center" style="vertical-align: middle">'
                            . __( 'No records are available in this view.', 'integration-dynamics' )
                            . '</td></tr></table>';

        if ( !$invoices || $invoices->Count < 1 ) {
            echo $noRecordsMessage;
            die();
        }

        if ( $invoices->MoreRecords && $invoices->PagingCookie != null ) {
            $pagingCookie = urlencode( $invoices->PagingCookie );
        } else {
            $pagingCookie = null;
        }

        $layout = new SimpleXMLElement( $lookup['layoutxml'] );

        $cells = $layout->xpath( ".//cell" );

        $output = '<table class="lookup-table">'
            . $this->renderTableHeader( $cells, $fetchDom, $invoices->Entities )
            . '<tbody>'
            . $this->renderTableResults( $cells, $fetchDom, $invoices->Entities )
            . '</tbody></table>';

        $response = [
            'data' => $output,
            'pagingcookie' => $pagingCookie,
            'morerecords' => ( $invoices->MoreRecords ) ? '1' : '0',
        ];

        echo json_encode( $response );

        // Always die in functions echoing ajax content
        die();
    }

    /**
     * Creates a response for the lookup request with search.
     */
    public function search_lookup_request() {
        // The $_REQUEST contains all the data sent via ajax
        if ( !isset( $_REQUEST ) || !isset( $_REQUEST['lookupType'] ) || !isset( $_REQUEST['searchstring'] ) ) {
            die();
        }

        $lookupType = $_REQUEST['lookupType'];
        $entity = ASDK()->entity( $lookupType );

        $returnedTypeCode = $entity->metadata()->objectTypeCode;

        $searchString = urldecode( $_REQUEST["searchstring"] );

        if ( $searchString != "" ) {
            $searchView = $this->retrieveLookupView( $returnedTypeCode, 4, $entity->metadata()->primaryNameAttribute );

            $fetchXML   = new SimpleXMLElement( $searchView['fetchxml'] );
            $conditions = $fetchXML->xpath( './/condition[@value]' );

            $searchXML = $fetchXML->asXML();

            foreach ( $conditions as $condition ) {
                $attribute = (string) $condition["attribute"][0];
                $value     = (string) $condition["value"][0];

                $oldCondition = $condition[0]->asXML();

                if ( $value == "{0}" ) {
                    if ( $entity->attributes[ $attribute ]->isLookup ) {
                        if ( preg_match( '/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/', $searchString ) ) {
                            $newCondition             = $condition;
                            $newCondition[0]["value"] = $searchString;
                            $searchXML                = str_replace( $oldCondition, $newCondition[0]->asXML(), $searchXML );
                        } else {
                            $newCondition             = $condition;
                            $newCondition[0]["value"] = AbstractClient::EmptyGUID;
                            $searchXML                = str_replace( $oldCondition, $newCondition[0]->asXML(), $searchXML );
                        }
                    } else {
                        $newCondition             = $condition;
                        $newCondition[0]["value"] = "%{$searchString}%";
                        $newCondition[0]["operator"] = 'like';
                        $searchXML                = str_replace( $oldCondition, $newCondition[0]->asXML(), $searchXML );
                    }
                }
            }

            $invoices = ASDK()->retrieveMultiple( $searchXML );
        } else {
            $invoices = ASDK()->retrieveMultipleEntities( $lookupType );
        }

        $lookup = $this->retrieveLookupView( $returnedTypeCode, 64, $entity->metadata()->primaryNameAttribute );

        $fetchDom = new DOMDocument();
        $fetchDom->loadXML( $lookup['fetchxml'] );

        $noRecordsMessage = '<table class="crm-popup-no-results"><tr><td align="center" style="vertical-align: middle">'
                            . __( 'No records are available in this view.', 'integration-dynamics' )
                            . '</td></tr></table>';

        if ( !$invoices || $invoices->Count < 1 ) {
            echo $noRecordsMessage;
            die();
        }

        $layout = new SimpleXMLElement( $lookup['layoutxml'] );

        $cells = $layout->xpath( ".//cell" );

        $output = '<table class="lookup-table">'
                  . $this->renderTableHeader( $cells, $fetchDom, $invoices->Entities )
                  . '<tbody>'
                  . $this->renderTableResults( $cells, $fetchDom, $invoices->Entities )
                  . '</tbody></table>';

        echo $output;

        // Always die in functions echoing ajax content
        die();
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
        $cache = ACRM()->cache;

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
            $result .= '<tr class="body-row" data-enitityid="' . $record->ID . '"  data-name="' . $record->displayname . '"><td><div class="lookup-checkbox"></div></td>';
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
                        $result .= 'linked' . $linkedRecord->getFormattedValue( $parts[1] );
                    }
                } else {
                    $formattedCellValue = $record->getFormattedValue( $cellName );

                    $cellValue = $formattedCellValue;

                    if ( ( $record->{$cellName} ) instanceof EntityReference
                         && ( $post = DataBinding::getDefaultPost( $record->{$cellName}->LOGICALNAME ) )
                    ) {
                        $permalink = get_permalink( $post );
                        $linkToPost = $permalink . ( strpos( $permalink, "?" )? '&' : '?' ) . "id={$record->{$cellName}->ID}";
                        $cellValue = "<a href=\"{$linkToPost}\">{$formattedCellValue}</a>";
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


