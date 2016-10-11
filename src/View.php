<?php

namespace AlexaCRM\WordpressCRM;

use AlexaCRM\CRMToolkit\Entity;
use DOMDocument;
use AlexaCRM\WordpressCRM\Shortcode\Field;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Fetches views from CRM and formats the values accordingly
 *
 * @package AlexaCRM\WordpressCRM
 */
class View {

    /**
     * Method to retrieve view entity record based on entity logical name for view and on the view display name
     *
     * @deprecated since version 1.0.37
     *
     * @param string $viewEntityName entity logical name for view savedquery or userquery
     * @param string $viewName
     *
     * @return Entity object that contain result view, or NULL if entity not found, or FALSE if error occured
     */
    public static function getView( $viewEntityName, $viewName ) {
        /* Check view entity types */
        if ( !in_array( $viewEntityName, array( "savedquery", "userquery" ) ) ) {
            trigger_error( "View entity logical name for view must be or savedquery or userquery" );

            return null;
        }
        /* Construct fetchXML to retrieve view entity */
        $fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
                            <entity name="' . $viewEntityName . '">
                                <attribute name="fetchxml" />
                                <attribute name="layoutxml" />
                                <attribute name="name" />
                                <filter type="and">
                                    <condition attribute="name" operator="eq" value="' . $viewName . '" />
                                </filter>
                            </entity>
                        </fetch>';

        /* Execute request and return response from Dynamics CRM web service */

        return ASDK()->retrieveSingle( $fetch );
    }

    /**
     * @param mixed $entity
     * @param string $viewName
     *
     * @return Entity object that contain result view, or NULL if entity not found, or FALSE if error occured
     */
    public static function getViewForEntity( $entity, $viewName, $viewEntityName = null ) {

        if ( is_string( $entity ) ) {

            $entity = ASDK()->entity( $entity );
        }
        $fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="true" count="1">
                            <entity name="userquery">
                                    <attribute name="fetchxml" />
                                    <attribute name="name" />
                                    <attribute name="returnedtypecode" />
                                    <attribute name="layoutxml" />
                                     <filter type="and">
                                            <condition attribute="returnedtypecode" operator="eq" value="' . $entity->metadata()->objectTypeCode . '" />
                                            <condition attribute="name" operator="eq" value="' . $viewName . '" />
                                      </filter>
                            </entity>
                      </fetch>';

        $view = ASDK()->retrieveSingle( $fetch );

        if ( $view == null ) {
            $fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="true" count="1">
                                <entity name="savedquery">
                                        <attribute name="fetchxml" />
                                        <attribute name="name" />
                                        <attribute name="returnedtypecode" />
                                        <attribute name="layoutxml" />
                                        <filter type="and">
                                            <condition attribute="returnedtypecode" operator="eq" value="' . $entity->metadata()->objectTypeCode . '" />
                                            <condition attribute="name" operator="eq" value="' . $viewName . '" />
                                        </filter>
                                </entity>
                          </fetch>';

            $view = ASDK()->retrieveSingle( $fetch );
        }

        return $view;
    }

    public static function getViewRows( $entities, $cells, $fetchXML, $timezoneoffset = null ) {

        $elements = array();

        if ( !$entities || !$entities->Count ) {
            return array();
        }

        $fetchDom = new DOMDocument();
        $fetchDom->loadXML( $fetchXML );

        $primaryName = $entities->Entities[0]->getPrimaryNameField();

        foreach ( $entities->Entities as $entity ) {

            $row = array();

            foreach ( $cells as $cell ) {

                $name = (string) $cell["name"];

                if ( strpos( $name, "." ) ) {
                    $parts = explode( ".", $name );

                    $alias = $parts[0];
                    $field = $parts[1];

                    if ( isset( $entity->$alias ) && isset( $entity->$alias->$field ) ) {
                        $element["head"]            = $entity->$alias->getPropertyLabel( $field );
                        $element["formatted_value"] = self::getFormattedValue( $entity->$alias, $field, $timezoneoffset );
                        $element["value"]           = $entity->$alias->$field;
                        $element["properties"]      = $entity->$alias->attributes[ $field ];
                    } else {
                        $element["head"]            = "";
                        $element["formatted_value"] = "";
                        $element["value"]           = null;
                        $element["properties"]      = null;
                    }
                } else {

                    $element["head"] = $entity->getPropertyLabel( $name );

                    if ( ( $entity->{$name} ) instanceof Entity ) {

                        if ( $post = DataBinding::getDefaultPost( $entity->{$name}->LOGICALNAME ) ) {
                            $permalink                  = get_permalink( $post->ID );
                            $post_querystring           = maybe_unserialize( get_post_meta( $post->ID, '_wordpresscrm_databinding_querystring', true ) );
                            $linktopost                 = ( strpos( $permalink, "?" ) ) ? $permalink . "&" . $post_querystring . "=" . $entity->{$name}->ID : $permalink . "?" . $post_querystring . "=" . $entity->{$name}->ID;
                            $element["formatted_value"] = "<a href='" . $linktopost . "'>" . $entity->getFormattedValue( $name, $timezoneoffset ) . "</a>";

                            $element["value"]      = $entity->{$name};
                            $element["properties"] = $entity->attributes[ $name ];
                        } else {

                            $element["formatted_value"] = self::getFormattedValue( $entity, $name, $timezoneoffset );
                            $element["value"]           = $entity->{$name};
                            $element["properties"]      = $entity->attributes[ $name ];
                        }
                    } else {

                        if ( $name == $primaryName && $post = DataBinding::getDefaultPost( $entity->logicalname ) ) {
                            $permalink                  = get_permalink( $post->ID );
                            $post_querystring           = maybe_unserialize( get_post_meta( $post->ID, '_wordpresscrm_databinding_querystring', true ) );
                            $linktopost                 = ( strpos( $permalink, "?" ) ) ? $permalink . "&" . $post_querystring . "=" . $entity->ID : $permalink . "?" . $post_querystring . "=" . $entity->ID;
                            $element["formatted_value"] = "<a href='" . $linktopost . "'>" . self::getFormattedValue( $entity, $name, $timezoneoffset ) . "</a>";

                            $element["value"]      = trim( $entity->{$name} );
                            $element["properties"] = $entity->attributes[ $name ];
                        } else {
                            $element["formatted_value"] = self::getFormattedValue( $entity, $name, $timezoneoffset );
                            $element["value"]           = trim( $entity->{$name} );
                            $element["properties"]      = $entity->attributes[ $name ];
                        }
                    }
                }

                $row[ $name ] = $element;
            }

            $elements[ $entity->ID ] = $row;
        }

        return $elements;
    }

    /**
     * Formats the given attribute according to its format
     *
     * @param Entity $entity Entity logical name
     * @param string $name Attribute name
     * @param int $timezoneoffset
     *
     * @return string
     */
    public static function getFormattedValue( $entity, $name, $timezoneoffset ) {

        $value = htmlentities( trim( $entity->getFormattedValue( $name, $timezoneoffset ) ) );

        if ( isset( $entity->attributes[ $name ] ) ) {
            switch ( $entity->attributes[ $name ]->format ) {
                case "Email":
                    $value = "<a href='mailto:" . $value . "'>" . $value . "</a>";
                    break;
                case "Url":
                    $value = "<a href='" . $value . "'>" . $value . "</a>";
                    break;
                case "Phone":
                    $value = "<a href='tel:" . $value . "'>" . $value . "</a>";
                    break;
            }
        }

        return $value;
    }

}
