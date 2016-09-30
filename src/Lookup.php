<?php

// Exit if accessed directly
namespace AlexaCRM\WordpressCRM;

use AlexaCRM\CRMToolkit\AbstractClient;
use AlexaCRM\CRMToolkit\Entity;
use AlexaCRM\WordpressCRM\Shortcode\Field;
use DOMDocument;
use Exception;
use SimpleXMLElement;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class Lookup {

    public function __construct() {
        $asd = ACRM();
        add_action( 'wp_ajax_retrieve_lookup_request', array( &$this, 'retrieve_lookup_request' ) );
        add_action( 'wp_ajax_search_lookup_request', array( &$this, 'search_lookup_request' ) );
        add_action( 'wp_ajax_nopriv_retrieve_lookup_request', array( &$this, 'retrieve_lookup_request' ) );
        add_action( 'wp_ajax_nopriv_search_lookup_request', array( &$this, 'search_lookup_request' ) );
    }

    public function retrieve_lookup_request() {
        // The $_REQUEST contains all the data sent via ajax
        if ( isset( $_REQUEST ) ) {

            $lookupType = $_REQUEST['lookupType'];

            $pagingCookie = null;

            if ( isset( $_REQUEST['pagingCookie'] ) && ( strlen( $_REQUEST['pagingCookie'] ) > 2 ) ) {
                $pagingCookie = urldecode( $_REQUEST['pagingCookie'] );
            }

            $pagingNumber = null;

            if ( isset( $_REQUEST['pageNumber'] ) && ( strlen( $_REQUEST['pageNumber'] ) > 0 ) ) {
                $pagingNumber = $_REQUEST['pageNumber'];
            }

            if ( $lookupType ) {

                $entity = ASDK()->entity( $lookupType );

                /* Query type for lookup views */
                $querytype = "64";

                $fetchView = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
                                <entity name="savedquery">
                                  <all-attributes  />
                                  <filter type="and">
                                    <condition attribute="querytype" operator="eq" value="' . $querytype . '" />
                                    <condition attribute="returnedtypecode" operator="eq" value="' . $entity->metadata()->objectTypeCode . '" />
                                  </filter>
                                </entity>
                              </fetch>';

                $lookupView = ASDK()->retrieveSingle( $fetchView );

                if ( $lookupView != null ) {
                    $fetchXML = $lookupView->fetchxml;

                    $fetchDom = new DOMDocument();
                    $fetchDom->loadXML( $fetchXML );
                } else {
                    throw new Exception( "Unable to get specified Savedquery" );
                }

                // add order for the first attribute in the FetchXML
                if ( !$fetchDom->getElementsByTagName( 'order' )->length ) {
                    $sortableAttribute = $fetchDom->getElementsByTagName( 'attribute' )->item( 0 );
                    $sortableAttributeName = $sortableAttribute->getAttribute( 'name' );
                    $orderElement = $sortableAttribute->parentNode->appendChild( $fetchDom->createElement( 'order' ) );
                    $orderElement->setAttribute( 'attribute', $sortableAttributeName );
                    $orderElement->setAttribute( 'descending', 'false' );

                    $fetchXML = $fetchDom->saveXML( $fetchDom->firstChild );
                }

                $invoices = ASDK()->retrieveMultiple( $fetchXML, false, $pagingCookie, 50, $pagingNumber );

                if ( !$invoices ) {
                    $output = '<table class="crm-popup-no-results"><tr><td align="center" style="vertical-align: middle">No records are available in this view.<td></tr></table>';
                    die();
                }

                if ( $invoices->Count < 1 ) {
                    $output = '<table class="crm-popup-no-results"><tr><td align="center" style="vertical-align: middle">No records are available in this view.<td></tr></table>';
                    die();
                }

                if ( $invoices->MoreRecords && $invoices->PagingCookie != null ) {
                    $pagingCookie = urlencode( $invoices->PagingCookie );
                } else {
                    $pagingCookie = null;
                }

                $output = "";

                $layout = new SimpleXMLElement( $lookupView->layoutxml );

                $cells = $layout->xpath( ".//cell" );

                $output .= '<table class="lookup-table"><thead>
                                <tr><th></th>';

                foreach ( $cells as $cell ) :

                    $output .= '<th><span>';

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
                            $output .= $invoices->Entities[0]->{$linkedEntityName}->getPropertyLabel( $parts[1] );
                        }
                    } else {

                        $output .= $invoices->Entities[0]->getPropertyLabel( (string) $cell["name"] );
                    }

                    $output .= '</span></th>';

                endforeach;

                $output .= '</tr></thead><tbody>';

                foreach ( $invoices->Entities as $invoice ) :

                    $output .= '<tr class="body-row" data-enitityid="' . $invoice->ID . '"  data-name="' . $invoice->displayname . '"><td><div class="lookup-checkbox"></div></td>';
                    foreach ( $cells as $cell ) :

                        $output .= '<td>';

                        if ( strpos( (string) $cell["name"], "." ) ) {
                            $parts = explode( ".", (string) $cell["name"] );

                            $output .= $invoice->{$parts[0]}->getFormattedValue( $parts[1] );
                        } else {

                            if ( ( $invoice->{(string) $cell["name"]} ) instanceof Entity ) {

                                if ( $post = Field::getDataBindPage( $invoice->{(string) $cell["name"]}->LOGICALNAME ) ) {

                                    $premalink = get_permalink( $post[0]->ID );

                                    $linktopost = ( strpos( $premalink, "?" ) ) ? $premalink . "&id=" . $invoice->{(string) $cell["name"]}->ID : $premalink . "?id=" . $invoice->{(string) $cell["name"]}->ID;

                                    $output .= "<a href='" . $linktopost . "'>" . (string) $invoice->getFormattedValue( (string) $cell["name"] ) . "</a>";
                                } else {

                                    $output .= (string) $invoice->getFormattedValue( (string) $cell["name"] );
                                }
                            } else {

                                $output .= (string) $invoice->getFormattedValue( (string) $cell["name"] );
                            }
                        }

                        $output .= '</td>';

                    endforeach;

                    $output .= '</tr>';

                endforeach;

                $output .= '</tbody></table>';

                $json["data"]         = $output;
                $json["pagingcookie"] = $pagingCookie;
                $json["morerecords"]  = ( $invoices->MoreRecords ) ? "1" : "0";

                $json = json_encode( $json );

                echo $json;
            }
        }

        // Always die in functions echoing ajax content
        die();
    }

    public function search_lookup_request() {
        // The $_REQUEST contains all the data sent via ajax
        if ( isset( $_REQUEST ) && isset( $_REQUEST["lookupType"] ) && isset( $_REQUEST["searchstring"] ) ) {

            $lookupType = $_REQUEST['lookupType'];

            $entity = ASDK()->entity( $lookupType );
            /* Query type for Search for lookup view */
            $querytype = "4";

            $fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
                            <entity name="savedquery">
                              <all-attributes  />
                              <filter type="and">
                                <condition attribute="querytype" operator="eq" value="' . $querytype . '" />
                                <condition attribute="isquickfindquery" operator="eq" value="true" />
                                <condition attribute="returnedtypecode" operator="eq" value="' . $entity->metadata()->objectTypeCode . '" />
                              </filter>
                            </entity>
                          </fetch>';

            $view = ASDK()->retrieveSingle( $fetch );

            $searchString = urldecode( $_REQUEST["searchstring"] );

            if ( $searchString != "" ) {

                $fetchxml   = new SimpleXMLElement( $view->fetchxml );
                $conditions = $fetchxml->xpath( './/condition[@value]' );

                $searchXML = $fetchxml->asXML();

                foreach ( $conditions as $condition ) {

                    $attribute = (string) $condition["attribute"][0];
                    $value     = (string) $condition["value"][0];

                    $oldCondition = $condition[0]->asXML();

                    if ( $value == "{0}" ) {

                        if ( $entity->attributes[ $attribute ]->isLookup == true ) {
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

            $fetchView = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
                            <entity name="savedquery">
                              <all-attributes  />
                              <filter type="and">
                                <condition attribute="querytype" operator="eq" value="64" />
                                <condition attribute="returnedtypecode" operator="eq" value="' . $entity->metadata()->objectTypeCode . '" />
                              </filter>
                            </entity>
                          </fetch>';

            $lookupView = ASDK()->retrieveSingle( $fetchView );

            if ( $view != null ) {
                $fetchXML = $lookupView->fetchxml;

                $fetchDom = new DOMDocument();
                $fetchDom->loadXML( $fetchXML );
            } else {
                throw new Exception( "Unable to get specified Savedquery" );
            }

            $noRecordsMessage = '<table class="crm-popup-no-results"><tr><td align="center" style="vertical-align: middle">'
                                . __( 'No records are available in this view.', 'wordpresscrm' )
                                . '</td></tr></table>';

            if ( !$invoices ) {
                echo $noRecordsMessage;
                die();
            }

            if ( $invoices->Count < 1 ) {
                echo $noRecordsMessage;
                die();
            }

            $output = "";

            $layout = new SimpleXMLElement( $lookupView->layoutxml );

            $cells = $layout->xpath( ".//cell" );

            $output .= '<table class="lookup-table"><thead>
                                <tr><th></th>';

            foreach ( $cells as $cell ) :

                $output .= '<th><span>';

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
                        $output .= $invoices->Entities[0]->{$linkedEntityName}->getPropertyLabel( $parts[1] );
                    }
                } else {

                    $output .= $invoices->Entities[0]->getPropertyLabel( (string) $cell["name"] );
                }

                $output .= '</span></th>';

            endforeach;

            $output .= '</tr></thead><tbody>';

            foreach ( $invoices->Entities as $invoice ) :

                $output .= '<tr class="body-row" data-enitityid="' . $invoice->ID . '"  data-name="' . $invoice->displayname . '"><td><div class="lookup-checkbox"></div></td>';
                foreach ( $cells as $cell ) :

                    $output .= '<td>';

                    if ( strpos( (string) $cell["name"], "." ) ) {
                        $parts = explode( ".", (string) $cell["name"] );

                        $output .= $invoice->{$parts[0]}->getFormattedValue( $parts[1] );
                    } else {

                        if ( ( $invoice->{(string) $cell["name"]} ) instanceof Entity ) {

                            if ( $post = Field::getDataBindPage( $invoice->{(string) $cell["name"]}->LOGICALNAME ) ) {

                                $premalink = get_permalink( $post[0]->ID );

                                $linktopost = ( strpos( $premalink, "?" ) ) ? $premalink . "&id=" . $invoice->{(string) $cell["name"]}->ID : $premalink . "?id=" . $invoice->{(string) $cell["name"]}->ID;

                                $output .= "<a href='" . $linktopost . "'>" . (string) $invoice->getFormattedValue( (string) $cell["name"] ) . "</a>";
                            } else {

                                $output .= (string) $invoice->getFormattedValue( (string) $cell["name"] );
                            }
                        } else {

                            $output .= (string) $invoice->getFormattedValue( (string) $cell["name"] );
                        }
                    }

                    $output .= '</td>';

                endforeach;

                $output .= '</tr>';

            endforeach;

            $output .= '</tbody></table>';

            echo $output;
        }

        // Always die in functions echoing ajax content
        die();
    }
}


