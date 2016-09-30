<?php

// Exit if accessed directly
namespace AlexaCRM\WordpressCRM\Shortcode;

use AlexaCRM\WordpressCRM\View as CRMView;
use AlexaCRM\WordpressCRM\Shortcode;
use SimpleXMLElement;
use AlexaCRM\WordpressCRM\FetchXML;
use AlexaCRM\WordpressCRM\Template;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * View shortcode [msdyncrm_view]
 *
 * @package AlexaCRM\WordpressCRM\Shortcode
 */
class View extends Shortcode {

    /**
     * View constructor.
     */
    public function __construct() {
        /* Get the options for the view, can be found in Dashboard > Dynamics CRM > Views tab */
        $options = ACRM()->option( "views" );

        /*
         * Check the options and define use the true/false images for boolean fields in the view grid
         * Images for boolean fields can be found in wordpress-crm/assets/images/control_imgs.png sprite image
         */
        if ( $options && !empty( $options['use_images_for_boolean'] ) ) {
            add_filter( 'wordpresscrm_view_images_boolean', '__return_true' );
        }
    }

    /**
     *
     *
     * @param array $atts Shortcode attributes to parse and assing default values
     *
     * @return array Parsed shortcode attributes
     */
    private static function parseAttributes( $atts ) {
        return shortcode_atts( array(
            'name'        => null,
            'entity'      => null,
            'entity_name' => null,
            'parameters'  => null,
            'lookups'     => array(),
            'allfields'   => false,
        ), $atts );
    }

    /**
     * Method process the fetchxml query and replace the values in it
     *
     * @param string $fetchXML fetch to retrieve the records for view
     * @param array $parameters that contain key => value array where key is placeholder to replace and value which can be simple string, currentuser, querystring and currentrecord fields
     * @param array $lookups array of lookup values that contain ["attribute"] => name with type_of_replacement.field that be currentuser, querystring, currentrecord
     * @param array $atrributes msdyncrm_view shortcode attributes array('name' => null, 'entity' => null, 'entity_name' => null, 'parameters' => null, 'lookups' => null, 'allfields' => false, )
     *
     * @return string the result fetchxml
     */
    public function constructFetchForView( $fetchXML, $parameters, $lookups, $atrributes = null ) {
        /* Replace the placeholders in condition statements in fetchXML with new values based on simple string or currentuser, querystring and currentrecord fields */
        $fetchXML = FetchXML::replacePlaceholderValuesByParametersArray( $fetchXML, $parameters );
        /* Replace <attribute/> tags with <all-attributes/> tag in fetchxml query*/
        $fetchXML = ( $atrributes["allfields"] == "true" ) ? FetchXML::constructAllAttributesFetch( $fetchXML ) : $fetchXML;
        $oldXML   = $fetchXML;
        /* Replacing the condition statements in fetchXML with new coditions based on currentuser, querystring and currentrecord fields */
        $fetchXML = FetchXML::replaceLookupConditionsByLookupsArray( $fetchXML, $lookups );
        if ( $oldXML == $fetchXML ) {
            /* Added support for deprecated lookups structure, where lookup condition was selected by uitype */
            $fetchXML = FetchXML::constructFetchForLookups( $fetchXML, $lookups );
        }
        /* Replacing the condition statements in fetchXML with new coditions based on currentuser, querystring and currentrecord fields */
        $fetchXML = FetchXML::findAndReplaceParameters( $fetchXML );

        /* Apply filters on the result fetchxml before send request to the Dynamics CRM */

        return apply_filters( "wordpresscrm_view_construct_fetch", $fetchXML, $atrributes["entity"], $atrributes["name"] );
    }

    /**
     * Shortcode handler
     *
     * @param array $atts
     * @param string $content
     * @param string $tagName
     *
     * @return string
     */
    public function shortcode( $atts, $content = null, $tagName ) {
        /* Check the connection to Dynamics CRM */
        if ( !ACRM()->connected() ) {
            /* Return error template if it's not connected function located in AlexaCRM\WordpressCRM\Shortcode::notConnected() */
            return self::notConnected();
        }
        //try {
        /* Parse msdyncrm_view shortcode attributes */
        $attributes = self::parseAttributes( $atts );
        /* Parse shortcode parameters attribute values if they exists */
        $parameters = self::parseKeyArrayShortcodeAttribute( $attributes["parameters"] );
        /* Parse shortcode lookups attribute values if they exists */
        $lookups  = self::parseKeyValueArrayShortcodeAttribute( $attributes["lookups"] );
        $fetchXML = null;
        $view     = null;

        /**
         * @var SimpleXMLElement $inlineTemplate
         */
        $inlineTemplate = ( $content ) ? apply_filters( 'wordpresscrm_view_inline', null, $this, $content ) : null;
        if ( $content && $inlineTemplate === null ) {
            return static::returnError( __( 'Inline view templates are not supported in this version.', 'wordpresscrm' ) );
        }
        if ( $content && !$inlineTemplate ) {
            return self::returnError( __( "Error in inline template body, please check the inline template for unclosed or empty tags. <br/> Note that inline template must be well formatted xml document", "wordpresscrm" ) );
        }
        if ( $inlineTemplate && $inlineTemplate->fetch ) {
            /* Check for the required attributes for the inline templates  */
            if ( !$inlineTemplate->results || !$inlineTemplate->noresults ) {
                return self::returnError( __( "<strong>The &lt;fetch&gt;, &lt;results&gt; and &lt;noresults&gt; nodes are required for inline templates</strong>, please construct fetch query and the results layot and add them into the shortcode body, <br />Example: [msdyncrm_view]&lt;fetch&gt;...&lt;/fetch&gt;&lt;results&gt;...&lt;/results&gt;&lt;noresults&gt;...&lt;/noresults&gt;[/msdyncrm_view]", "wordpresscrm" ) );
            }
            $fetchXML = $inlineTemplate->fetch->asXML();
        }

        /* If there is no inline template, check the required shortcode attributes and retrieve view entity record */
        if ( !$fetchXML ) {
            /* Check deprecated entity_name shortcode attribute */
            if ( $attributes["entity_name"] ) {
                /* Return error template with details of deprecated attributes and instructions what need to change to make it works */
                return self::returnError( __( "\"entity_name\" shortcode attribute is deprecated, use \"entity\" shortcode attribute instead with result records entity type", "wordpresscrm" ) );
            }
            /* Check for required "entity" shortcode attribute */
            if ( !$attributes["entity"] ) {
                return self::returnError( __( "\"entity\" shortcode attribute is required, please add entity=\"entity_logical_name\" to the msdyncrm_view shortcode attributes", "wordpresscrm" ) );
            }
            /* Check for required "name" shortcode attribute */
            if ( !$attributes["name"] ) {
                return self::returnError( __( "\"name\" shortcode attribute is required, please add name=\"View Name\" to the msdyncrm_view shortcode attributes", "wordpresscrm" ) );
            }
            /* Retrieve the view entity record by entity name of the result records type and view name */

            $cacheKey = 'wpcrm_view_' . sha1( 'entity_' . $attributes['entity'] . '_view_' . $attributes['name'] );
            $cache = ACRM()->cache;
            $view = $cache->get( $cacheKey );
            if ( $view == null ) {
                $crmView = CRMView::getViewForEntity( strtolower( $attributes["entity"] ), $attributes["name"] );

                if ( $crmView == null ) {
                    return self::returnError( "Unable to get specified savedquery (Query name: " . $attributes["name"] . " of entity: " . $attributes["entity"] . ")" );
                }

                $view = [
                    'fetchxml' => $crmView->fetchxml,
                    'layoutxml' => $crmView->layoutxml,
                ];

                $cache->set( $cacheKey, $view, 10 * 60 );
            }

            $fetchXML = $view['fetchxml'];
        }
        /* Replace the lookups and parameter condition in fetchxml, and add <all-attributes/> tag if it's configured in shortcode attributes */
        $fetchXML = $this->constructFetchForView( $fetchXML, $parameters, $lookups, $attributes );

        $entities = $this->retrieveRecords( $fetchXML, $attributes, $parameters, $lookups );

        if ( $entities && !$entities->Count && $inlineTemplate && $inlineTemplate->noresults ) {
            return $inlineTemplate->noresults->asXML();
        }

        if ( !empty( $inlineTemplate->fetch ) ) {
            $cells = array();
            /* Parse fetch for cells */
            foreach ( $inlineTemplate->fetch->entity->children() as $key => $node ) {
                if ( $key == "attribute" ) {
                    $cell["name"] = (string) $node["name"];
                    array_push( $cells, $cell );
                }
            }
            if ( count( $inlineTemplate->xpath( '//link-entity/attribute' ) ) ) {
                foreach ( $inlineTemplate->xpath( '//link-entity/attribute' ) as $node ) {
                    $linkEntityNode = $node->xpath( '..' )[0];
                    $cell['name'] = (string)$linkEntityNode['alias'] . '.' . (string)$node['name'];
                    array_push( $cells, $cell );
                }
            }
        } else {
            $layout = new SimpleXMLElement( $view['layoutxml'] );
            $cells  = $layout->xpath( ".//cell" );
        }

        $rows = CRMView::getViewRows( $entities, $cells, $fetchXML, null, ( $inlineTemplate && $inlineTemplate->results ) );

        if ( $inlineTemplate && $inlineTemplate->results && $entities->Count ) {
            ob_start();
            self::recursiveOut( $inlineTemplate->results[0], $entities->Entities, $rows );

            return ob_get_clean();
        }

        $templatePath = Template::locateShortcodeTemplate( "view/view", $attributes["entity"], $attributes["name"] );

        return Template::printTemplate( $templatePath, array(
            "rows"       => $rows,
            "entities"   => $entities,
            "cells"      => $cells,
            "attributes" => $attributes
        ) );
    }

    /**
     * Traverses the results template and constructs the resulting view
     *
     * @param $results
     * @param $entities
     * @param null $rows
     */
    public static function recursiveOut( $results, $entities, $rows = null ) {
        foreach ( $results as $key => $result ) {
            if ( $result->count() ) {
                if ( in_array( $key, array( "foreachentity", "foreach" ) ) ) {
                    if ( is_array( $entities ) ) {
                        foreach ( $entities as $entity ) {
                            self::recursiveOut( $result, $entity, $rows );
                        }
                    }
                } else if ( $key == "foreachrow" && $rows ) {
                    if ( is_array( $rows ) ) {
                        foreach ( $rows as $row ) {
                            self::recursiveOut( $result, $entities, $row );
                        }
                    }
                } else if ( $key == "foreachcell" && $rows ) {
                    if ( is_array( $rows ) ) {
                        foreach ( $rows as $row ) {
                            self::recursiveOut( $result, $entities, $row );
                        }
                    }
                } else {
                    echo "<" . $key . self::addAttributes( $result, $entities, $rows ) . ">";
                    if ( preg_match_all( '/\\$cell/', $result, $matches ) ) {
                        $out = $result;
                        foreach ( array_unique( $matches[0] ) as $match ) {
                            $out = str_replace( $match, ( !empty( $rows["formatted_value"] ) ? $rows["formatted_value"] : "" ), $out );
                        }
                        echo $out;
                    } else if ( preg_match_all( '/\\$row(\\.[a-zA-Z0-9_]+)+/', $result, $matches ) ) {
                        $out = $result;
                        foreach ( array_unique( $matches[0] ) as $match ) {
                            $out = self::replaceRow( $out, $match, $rows );
                        }
                        echo $out;
                    } else if ( preg_match_all( '/\\$entity\\.[a-zA-Z0-9_]+/', $result, $matches ) ) {
                        $out = $result;
                        foreach ( array_unique( $matches[0] ) as $match ) {
                            $out = str_replace( $match, $entities->getFormattedValue( str_replace( "\$entity.", "", $match ), null ), $out );
                        }
                        echo $out;
                    } else {
                        echo $result;
                    }
                    self::recursiveOut( $result, $entities, $rows );
                    echo "</" . $key . ">";
                }
            } else {
                if ( $key == "foreachcell" && $rows ) {
                    if ( is_array( $rows ) ) {
                        foreach ( $rows as $row ) {
                            self::recursiveOut( $result, $entities, $row );
                        }
                    }
                } else if ( preg_match_all( '/\\$cell/', $result->saveXml(), $matches ) ) {
                    echo "<" . $key . self::addAttributes( $result, $entities, $rows ) . ">";
                    $out = $result;
                    foreach ( array_unique( $matches[0] ) as $match ) {
                        $out = str_replace( $match, ( !empty( $rows["formatted_value"] ) ? $rows["formatted_value"] : "" ), $out );
                    }
                    echo $out;
                    echo "</" . $key . ">";
                } else if ( preg_match_all( '/\\$row(\\.[a-zA-Z0-9_]+)+/', $result->saveXml(), $matches ) ) {
                    $out = $result;
                    echo "<" . $key . self::addAttributes( $result, $entities, $rows ) . ">";
                    foreach ( array_unique( $matches[0] ) as $match ) {
                        $out = self::replaceRow( $out, $match, $rows );
                    }
                    echo $out;
                    echo "</" . $key . ">";
                } else if ( preg_match_all( '/\\$entity\\.[a-zA-Z0-9_]+/', $result->saveXml(), $matches ) ) {
                    $out = $result;
                    echo "<" . $key . self::addAttributes( $result, $entities, $rows ) . ">";
                    foreach ( array_unique( $matches[0] ) as $match ) {
                        $out = str_replace( $match, $entities->getFormattedValue( str_replace( "\$entity.", "", $match ), null ), $out );
                    }
                    echo $out;
                    echo "</" . $key . ">";
                } else {
                    echo $result->asXML();
                }
            }
        }
    }

    /**
     * Replaces the row with a formatted value
     *
     * @param $result
     * @param $match
     * @param $rows
     *
     * @return mixed
     */
    public static function replaceRow( $result, $match, $rows ) {

        $key = str_replace( "\$row.", "", $match );

        $value = "";
        if ( !empty( $rows[ $key ]["formatted_value"] ) ) {
            $value = $rows[ $key ]["formatted_value"];
        }
        if ( !$value ) {
            $value = (string) $rows[ $key ]["value"];
        }

        return str_replace( $match, ( !empty( $rows[ str_replace( "\$row.", "", $match ) ]["formatted_value"] ) ? $rows[ str_replace( "\$row.", "", $match ) ]["formatted_value"] : "" ), $result );
    }

    /**
     * Adds entity attributes to the template
     *
     * @param $node
     * @param null $entity
     * @param null $cell
     *
     * @return string
     */
    public static function addAttributes( $node, $entity = null, $cell = null ) {
        $attributes = '';
        foreach ( $node->attributes() as $key => $atr ) {
            if ( preg_match_all( '/\\$cell/', $atr, $matches ) ) {
                foreach ( array_unique( $matches[0] ) as $match ) {
                    $atr = str_replace( $match, ( !empty( $rows["formatted_value"] ) ? $rows["formatted_value"] : "" ), $atr );
                }
            } elseif ( preg_match_all( '/\\$row(\\.[a-zA-Z0-9_]+)+/', $atr, $matches ) ) {
                foreach ( array_unique( $matches[0] ) as $match ) {
                    $atr = str_replace( $match, ( !empty( $cell[ str_replace( "\$row.", "", $match ) ]["formatted_value"] ) ? $cell[ str_replace( "\$row.", "", $match ) ]["formatted_value"] : "" ), $atr );
                }
            } else if ( preg_match_all( '/\\$entity\\.[a-zA-Z0-9_]+/', $atr, $matches ) ) {
                foreach ( array_unique( $matches[0] ) as $match ) {
                    $atr = str_replace( $match, $entity->getFormattedValue( str_replace( "\$row.", "", $match ) ), $atr );
                }
            }
            $attributes .= " " . $key . '="' . $atr . '"';
        }

        return $attributes;
    }

    /**
     * Retrieves records by passing given FetchXML to the CRM
     *
     * @param string $fetchXML
     * @param $attributes
     * @param $parameters
     * @param $lookups
     *
     * @return mixed
     */
    function retrieveRecords( $fetchXML, $attributes, $parameters, $lookups ) {
        $entities = ASDK()->retrieveMultiple( $fetchXML );

        return apply_filters( "wordpresscrm_view_entities", $entities, $attributes["entity"], $attributes["name"], $parameters, $lookups );
    }

    /**
     * @param $content
     *
     * @return string
     */
    function prepareXML( $content ) {
        return "<content>" . htmlspecialchars_decode( $this->removeSmartQuotes( $this->reverse_wpautop( $content ) ), ENT_QUOTES ) . "</content>";
    }

    /**
     * @param $content
     *
     * @return mixed
     */
    function removeSmartQuotes( $content ) {
        return str_replace( array( "&#8220;", "&#8221;", "&#8243;" ), "&quot;", str_replace( array(
            "&#8216;",
            "&#8217;"
        ), "&#39;", $content ) );
    }

    /**
     * @param $s
     *
     * @return mixed
     */
    function reverse_wpautop( $s ) {
        //remove any new lines already in there
        return str_replace( array( "<br />", "<br>", "<br/>", "<p>", "</p>" ), "", $s );
    }

    /**
     * Checks whether given XML is valid
     *
     * @param string $xml
     *
     * @return bool|string
     */
    public function isValidXML( $xml ) {
        if ( function_exists( 'libxml_use_internal_errors' ) ) {
            libxml_use_internal_errors( true );
            $doc = simplexml_load_string( $xml );
            if ( $doc === false ) {
                foreach ( libxml_get_errors() as $error ) {
                    echo "\n \t", $error->message;
                }

                return false;
            } else {
                return $xml; //this is valid
            }
        } else {
            $doc = @simplexml_load_string( $xml );
            if ( $doc ) {
                return $xml; //this is valid
            } else {
                return false; //this is not valid
            }
        }
    }

}
