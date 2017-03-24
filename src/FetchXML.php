<?php

namespace AlexaCRM\WordpressCRM;

use AlexaCRM\CRMToolkit\AbstractClient;
use AlexaCRM\CRMToolkit\Entity;
use SimpleXMLElement;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * A collection of helper methods to adjust FetchXML queries for special cases
 *
 * @package AlexaCRM\WordpressCRM
 */
class FetchXML {

    /**
     * Replace the placeholder in the fetchxml conditions(like {0} or {1}) with the value from the GET parameter if it exists, if GET parameter doesn't set return the imput fetchxml
     *
     * @param string $fetchXML fetchxml string to retrieve the entity records
     * @param string $placeholder value that need to be replaced (like {0} or {1})
     * @param string $value the key of the GET parameter arg, $_GET[$value]
     *
     * @return string fetchxml with replaced placeholder with value from the GET parameter if it exists, if GET parameter doesn't set return the input fetchxml
     */
    public static function replaceConditionPlaceholderByQuerystringValue( $fetchXML, $placeholder, $value ) {
        $query = ACRM()->request->query;

        $queryStringValue = $query->get( $value );
        if ( $queryStringValue ) {
            return str_replace( $placeholder, htmlspecialchars( $queryStringValue ), $fetchXML );
        }

        return $fetchXML;
    }

    /**
     * Replace the placeholder in the fetchxml conditions(like {0} or {1}) with the value from currentrecord (Data-bound) entity field
     *
     * @param string $fetchXML fetchxml to retrieve the entity records
     * @param string $placeholder value that need to be replaced (like {0} or {1})
     * @param string $value the field of currentrecord's (Data-bound) entity, like "id" or "displayname"
     *
     * @return string string fetchxml with replaced placeholder with value from currentrecord's entity field, if there is no data-bound record assigned to page or field doesn't contain value return the input fetcxml
     */
    public static function replaceConditionPlaceholderByCurrentrecordFieldValue( $fetchXML, $placeholder, $value ) {
        $record = ACRM()->getBinding()->getEntity();

        return ( $record != null && $record->ID && isset( $record->$value ) ) ? str_replace( $placeholder, htmlspecialchars( $record->$value ), $fetchXML ) : $fetchXML;
    }

    /**
     * Replace the placeholder in the fetchxml conditions(like {0} or {1}) with the string value
     *
     * @param string $fetchXML fetchxml to retrieve the entity records
     * @param string $placeholder value that need to be replaced (like {0} or {1})
     * @param string $value string value to for adding into fetchxml
     *
     * @return string string fetchxml with replaced placeholder with value
     */
    public static function replaceConditionPlaceholderByValue( $fetchXML, $placeholder, $value ) {
        return str_replace( $placeholder, $value, $fetchXML );
    }

    /**
     * Replacing the placeholders in condition statements in fetchXML with new values based on simple string or currentuser, querystring and currentrecord fields from $parametes array
     *
     * @param string $fetchXML fetchxml to retrieve the entity records
     * @param array $parameters that contain key => value array where key is placeholder to replace and value which can be simple string, currentuser, querystring and currentrecord fields
     *
     * @return string result of fetchxml processing
     */
    public static function replacePlaceholderValuesByParametersArray( $fetchXML, $parameters = [] ) {
        /* Check each parameter key => value array where key is placeholder to replace in fetchxml and param is value to replace */
        foreach ( $parameters as $placeholder => $param ) {
            $param = explode( ".", trim( $param ) );
            $type  = trim( $param[0] );
            $value = ( !empty( $param[1] ) ) ? trim( $param[1] ) : null;
            /* Wrap the placeholder into brackets to find it in the fetchxml conditions */
            $placeholder = sprintf( "{%s}", $placeholder );
            if ( $value == null ) {
                /* Replace the placeholder in xml with simple string value */
                $fetchXML = self::replaceConditionPlaceholderByValue( $fetchXML, $placeholder, $type );
            } else {
                if ( $type == "querystring" ) {
                    $fetchXML = self::replaceConditionPlaceholderByQuerystringValue( $fetchXML, $placeholder, $value );
                } else if ( $type == "currentrecord" ) {
                    $fetchXML = self::replaceConditionPlaceholderByCurrentrecordFieldValue( $fetchXML, $placeholder, $value );
                } else {
                    $fetchXML = apply_filters( 'wordpresscrm_replace_placeholders', $fetchXML, $placeholder, $value, $type );
                }
            }
        }

        return $fetchXML;
    }

    /**
     * Function to find the condition statement by specified attribute name and attribute value and replace it's value attribute with the $value variable in provided fetchxml
     *
     * @param string $fetchXML fetchxml to retrieve the entity records
     * @param string $attribute name of the attribute of the condition node to find the condition, for exaple <b>attribute</b>="customerid"
     * @param string $attributeValue value of the attribute to find condition, for exaple attribute="<b>customerid</b>"
     * @param string $value of the condition node, value="$value"
     *
     * @return string $fetchXML with replaced condition
     */
    public static function replaceCondition( $fetchXML, $attribute, $attributeValue, $value ) {
        $query      = new SimpleXMLElement( $fetchXML );
        $conditions = $query->xpath( './/condition[@' . $attribute . '="' . $attributeValue . '"]' );
        if ( count( $conditions ) > 0 ) {
            foreach ( $conditions as $condition ) {
                $newCondition = $condition;
                unset( $newCondition[0]["uiname"] );
                unset( $newCondition[0]["uitype"] );
                $newCondition[0]["value"] = "{" . $value . "}";
                $fetchXML                 = $fetchXML = str_replace( '<?xml version="1.0"?>', "", str_replace( $condition[0]->asXML(), $newCondition[0]->asXML(), $query->asXML() ) );
            }
        }

        return $fetchXML;
    }

    /**
     * Replace condition statements in FetchXML using conditions provided in $lookups.
     *
     * @param string $fetchXML FetchXML to retrieve the records for view.
     * @param array $lookups Array of lookup values [ attribute => lookupCondition ].
     *
     * @return string FetchXML with replaced conditions.
     */
    public static function replaceLookupConditionsByLookupsArray( $fetchXML, $lookups = [] ) {
        foreach ( $lookups as $key => $param ) {
            $param = explode( ".", $param );
            $type  = trim( $param[0] );
            $value = trim( $param[1] );

            if ( !in_array( $type, [ 'currentrecord', 'querystring' ], true ) || $value === '' ) {
                $fetchXML = apply_filters( 'wordpresscrm_replace_lookups', $fetchXML, $key, $value, $type );

                continue;
            }

            $replaceValue = null;
            if ( $type === 'currentrecord' ) {
                /* Replace values with current data bound page (if data-binding or current entity exists) */
                $record = ACRM()->getBinding()->getEntity();

                if ( $record instanceof Entity && $record->ID ) {
                    $replaceValue = $record->ID;
                }
            } elseif ( $type === 'querystring' ) {
                /* Replace values with querystring values */
                $query = ACRM()->request->query;

                if ( $query->get( $value, '' ) !== '' ) {
                    $replaceValue = $query->get( $value );
                }
            }

            if ( $replaceValue === null ) {
                $replaceValue = AbstractClient::EmptyGUID;
                add_filter( "wordpresscrm_view_entities", "__return_false" );
            }

            $fetchXML = self::replaceCondition( $fetchXML, 'attribute', $key, $replaceValue );
        }

        return $fetchXML;
    }

    /**
     * Parse the fetchxml string for {currentrecord.field}, {currentuser.field}, {querystring.field} and replace them with corresponding values from entities or querystring
     * Usualy used to proccess view inline templates fetch xml
     *
     * @param string $fetchXML fetch to retrieve the records for view
     *
     * @return string fetchXML with replaced values
     */
    public static function findAndReplaceParameters( $fetchXML ) {
        if ( preg_match_all( '/{currentrecord\\.[a-zA-Z0-9]+}/', $fetchXML, $matches ) ) {
            foreach ( array_unique( $matches[0] ) as $match ) {
                list( $firstPart, $value ) = explode( ".", $match );
                $fetchXML = self::replaceConditionPlaceholderByCurrentrecordFieldValue( $fetchXML, $match, str_replace( array(
                    "{",
                    "}"
                ), "", $value ) );
            }
        }
        if ( preg_match_all( '/{querystring\\.[a-zA-Z0-9]+}/', $fetchXML, $matches ) ) {
            foreach ( array_unique( $matches[0] ) as $match ) {
                list( $firstPart, $value ) = explode( ".", $match );
                $fetchXML = self::replaceConditionPlaceholderByQuerystringValue( $fetchXML, $match, str_replace( array(
                    "{",
                    "}"
                ), "", $value ) );
            }
        }

        $fetchXML = apply_filters( 'wordpresscrm_replace_parameters', $fetchXML );

        return $fetchXML;
    }

    /**
     * Replace single <attribute/> tags with <all-attributes/> tag in fetchxml
     *
     * @param string $fetchXML fetch to retrieve the records for view
     *
     * @return string fetchXML with replaced stripped <attribute/> tags and added <all-attributes/> tag
     */
    public static function constructAllAttributesFetch( $fetchXML ) {
        $query            = new SimpleXMLElement( $fetchXML );
        $allAttributesTag = $query->xpath( './/all-attributes' );
        if ( empty( $allAttributesTag ) ) {
            foreach ( $query->xpath( './/attribute' ) as $a ) {
                unset( $a[0] );
            }
            $entity = $query->xpath( './/entity' );
            $entity[0]->addChild( "all-attributes" );
            $fetchXML = str_replace( '<?xml version="1.0"?>', "", $query->asXML() );
        }

        return $fetchXML;
    }

    /**
     * Replacing the condition statements in fetchXML with new coditions based on currentuser, querystring and currentrecord fields from $lookups array
     *
     * @deprecated since version 1.0.37
     *
     * @param string $fetchXML fetch to retrieve the records for view
     * @param array $lookups array of lookup values that contain ["uitype"] => type_of_replacement.field type of replacement can be currentuser, querystring, currentrecord
     *
     * @return string fetchXML with replaced conditions
     */
    public static function constructFetchForLookups( $fetchXML, $lookups = Array() ) {
        foreach ( $lookups as $key => $param ) {
            $param = explode( ".", $param );
            $type  = trim( $param[0] );
            $value = trim( $param[1] );
            /* Replace values with current data bound page (if data-binding or current entity exists) */
            if ( $type == "currentrecord" && $value ) {
                $record = ACRM()->getBinding()->getEntity();
                if ( $record != null && $record->ID && strtolower( $key ) == $record->logicalname ) {
                    $fetchXML = self::replaceCondition( $fetchXML, 'uitype', $key, $record->ID );
                } else {
                    $fetchXML = self::replaceCondition( $fetchXML, 'uitype', $key, AbstractClient::EmptyGUID );
                    add_filter( "wordpresscrm_view_entities", "__return_false" );
                }
            }
            /* Replace the lookup condition with querystring values for lookups */
            if ( $type == "querystring" && $value ) {
                if ( isset( $_GET[ $value ] ) && $_GET[ $value ] ) {
                    $fetchXML = self::replaceCondition( $fetchXML, 'uitype', $key, $_GET[ $value ] );
                } else {
                    $fetchXML = self::replaceCondition( $fetchXML, 'uitype', $key, AbstractClient::EmptyGUID );
                    add_filter( "wordpresscrm_view_entities", "__return_false" );
                }
            }
            $fetchXML = apply_filters( 'wordpresscrm_construct_fetch', $fetchXML, $key, $value, $type );
        }
        return $fetchXML;
    }

}
