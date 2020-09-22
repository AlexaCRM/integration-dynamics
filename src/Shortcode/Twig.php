<?php

namespace AlexaCRM\WordpressCRM\Shortcode;

use AlexaCRM\CRMToolkit\Client;
use AlexaCRM\CRMToolkit\Entity;
use AlexaCRM\CRMToolkit\Entity\EntityReference;
use AlexaCRM\WordpressCRM\Cache\TwigCache;
use AlexaCRM\WordpressCRM\Shortcode;
use DOMDocument;
use Symfony\Component\HttpFoundation\Request;
use Twig_Extension_Debug;

/**
 * Implements the Twig templates with entity/user binding,
 * views, forms, etc.
 */
class Twig extends Shortcode {

    /**
     * @var \Twig_Environment
     */
    protected static $twigEnvironment;

    /**
     * Array loader that stores inline shortcode templates.
     *
     * @var \Twig_Loader_Array
     */
    protected static $shortcodeLoader;

    /**
     * Renders the shortcode.
     *
     * @param array $attributes
     * @param string $content
     * @param string $tagName
     *
     * @return string
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function shortcode( $attributes, $content, $tagName ) {
        $twig = $this->getTwig();

        // Remove wpautop() side effects
        $content = trim( static::reverse_wpautop( $content ) );

        $templateKey = 'template_' . sha1( $content );
        static::$shortcodeLoader->setTemplate( $templateKey, $content );

        $output = $twig->render( $templateKey );

        // Process nested shortcodes
        return do_shortcode( $output );
    }

    /**
     * Gives access to the Twig engine instance.
     *
     * @return \Twig_Environment
     */
    protected function getTwig() {
        if ( static::$twigEnvironment instanceof \Twig_Environment ) {
            return static::$twigEnvironment;
        }

        $chainLoader = new \Twig_Loader_Chain();

        static::$shortcodeLoader = new \Twig_Loader_Array();
        $chainLoader->addLoader( static::$shortcodeLoader );
        $chainLoader->addLoader(
            new \Twig_Loader_Filesystem( WORDPRESSCRM_DIR . '/templates/twig' )
        );

        /**
         * Allows extending the list of available Twig template loaders.
         *
         * @param \Twig_Loader_Chain $chainLoader
         */
        do_action( 'wordpresscrm_after_twig_loaders', $chainLoader );

        $isDebugEnabled = defined( 'WP_DEBUG' ) && WP_DEBUG;
        $twigCache = false;
        if ( !defined( 'WORDPRESSCRM_TWIG_CACHE_DISABLE' ) || !WORDPRESSCRM_TWIG_CACHE_DISABLE ) {
            $twigCache = new TwigCache( WORDPRESSCRM_STORAGE . '/twig' );
        }

        $twigEnv = new \Twig_Environment( $chainLoader, [
            'debug' => $isDebugEnabled,
            'cache' => $twigCache,
        ] );
        $twigEnv->setBaseTemplateClass( '\AlexaCRM\WordpressCRM\Shortcode\Twig\Template' );

        if ( $isDebugEnabled ) {
            $twigEnv->addExtension( new Twig_Extension_Debug() );
        }

        // Add global variables to the context
        $this->addGlobals( $twigEnv );

        // Add filters to the environment
        $this->addFilters( $twigEnv );

        // `fetchxml` tag
        $twigEnv->addTokenParser( new Twig\TokenParsers\FetchxmlTokenParser() );

        // `view` tag
        $twigEnv->addTokenParser( new Twig\TokenParsers\ViewTokenParser() );

        // `form` tag
        $twigEnv->addTokenParser( new Twig\TokenParsers\FormTokenParser() );

        // entityUrl() - URL builder
        $entityUrlFunction = new \Twig_SimpleFunction( 'entityUrl', function( $entityName, $entityId ) {
            $binding = ACRM()->getBinding();
            $reference = new EntityReference( $entityName, $entityId );

            return $binding->buildUrl( $reference );
        } );
        $twigEnv->addFunction( $entityUrlFunction );

        // attachmentUrl() - URL generator for force-downloaded attachments
        $attachmentUrlFunction = new \Twig_SimpleFunction( 'attachmentUrl', function( $attachmentId ) {
            return admin_url( 'admin-ajax.php?action=msdyncrm_attachment&id=' . $attachmentId );
        } );
        $twigEnv->addFunction( $attachmentUrlFunction );

        // Provide access to global OptionSet metadata.
        $globalOptionSetFunc = new \Twig_SimpleFunction( 'globaloptionset', function( $name ) {
            $reqDom = new DOMDocument();
            $execNode              = $reqDom->appendChild( $reqDom->createElementNS( 'http://schemas.microsoft.com/xrm/2011/Contracts/Services', 'Execute' ) );
            $reqNode              = $execNode->appendChild( $reqDom->createElement( 'request' ) );
            $reqNode->setAttributeNS( 'http://www.w3.org/2001/XMLSchema-instance', 'i:type', 'b:RetrieveOptionSetRequest' );
            $reqNode->setAttributeNS( 'http://www.w3.org/2000/xmlns/', 'xmlns:b', 'http://schemas.microsoft.com/xrm/2011/Contracts' );
            $paramNode = $reqNode->appendChild( $reqDom->createElement( 'b:Parameters' ) );
            $paramNode->setAttributeNS( 'http://www.w3.org/2000/xmlns/', 'xmlns:c', 'http://schemas.datacontract.org/2004/07/System.Collections.Generic' );
            /* EntityFilters */
            $kvPairNode1 = $paramNode->appendChild( $reqDom->createElement( 'b:KeyValuePairOfstringanyType' ) );
            $kvPairNode1->appendChild( $reqDom->createElement( 'c:key', 'Name' ) );
            $valNode1 = $kvPairNode1->appendChild( $reqDom->createElement( 'c:value', $name ) );
            $valNode1->setAttribute( 'i:type', 'd:string' );
            $valNode1->setAttributeNS( 'http://www.w3.org/2000/xmlns/', 'xmlns:d', 'http://www.w3.org/2001/XMLSchema' );

            /* MetadataId */
            $keyValuePairNode2 = $paramNode->appendChild( $reqDom->createElement( 'b:KeyValuePairOfstringanyType' ) );
            $keyValuePairNode2->appendChild( $reqDom->createElement( 'c:key', 'MetadataId' ) );
            $valueNode2 = $keyValuePairNode2->appendChild( $reqDom->createElement( 'c:value', Client::EmptyGUID ) );
            $valueNode2->setAttribute( 'i:type', 'd:guid' );
            $valueNode2->setAttributeNS( 'http://www.w3.org/2000/xmlns/', 'xmlns:d', 'http://schemas.microsoft.com/2003/10/Serialization/' );

            /* RetrieveAsIfPublished */
            $keyValuePairNode3 = $paramNode->appendChild( $reqDom->createElement( 'b:KeyValuePairOfstringanyType' ) );
            $keyValuePairNode3->appendChild( $reqDom->createElement( 'c:key', 'RetrieveAsIfPublished' ) );
            $valueNode3 = $keyValuePairNode3->appendChild( $reqDom->createElement( 'c:value', 'false' ) );
            $valueNode3->setAttribute( 'i:type', 'd:boolean' );
            $valueNode3->setAttributeNS( 'http://www.w3.org/2000/xmlns/', 'xmlns:d', 'http://www.w3.org/2001/XMLSchema' );

            /* Request ID and Name */
            $reqNode->appendChild( $reqDom->createElement( 'b:RequestId' ) )->setAttribute( 'i:nil', 'true' );
            $reqNode->appendChild( $reqDom->createElement( 'b:RequestName', 'RetrieveOptionSet' ) );

            $respXml = ACRM()->getSdk()->attemptSoapResponse( 'organization', function() use ( $execNode ) {
                return ACRM()->getSdk()->generateSoapRequest( 'organization', 'Execute', $execNode );
            } );
            $resp = new DOMDocument();
            $resp->loadXML( $respXml );
            $respQ = new \DOMXPath( $resp );
            $respQ->registerNamespace( 'z', 'http://schemas.microsoft.com/xrm/2011/Contracts/Services' );
            $respQ->registerNamespace( 'b', 'http://schemas.microsoft.com/xrm/2011/Contracts' );
            $respQ->registerNamespace( 'c', 'http://schemas.datacontract.org/2004/07/System.Collections.Generic' );

            $mdNodes = $respQ->query( '//z:ExecuteResult/b:Results/b:KeyValuePairOfstringanyType/c:value' );
            if ( $mdNodes === false || $mdNodes->length === 0 ) {
                return null;
            }

            $mdNode = $mdNodes->item( 0 );
            $mdXml = $resp->saveXML( $mdNode );
            $mdXml = preg_replace( '/(<)([a-z]:)/', '<', preg_replace( '/(<\/)([a-z]:)/', '</', $mdXml ) );
            $mdXml = preg_replace( '~([\s"])[a-z]:([a-zA-Z]+)~', '$1$2', $mdXml );

            $os = new Entity\OptionSet( simplexml_load_string( $mdXml ) );

            return $os;
        } );
        $twigEnv->addFunction( $globalOptionSetFunc );

        /**
         * Fired when Twig environment has been set up in the shortcode.
         *
         * Allows to further extend the Twig environment with new features.
         *
         * @param \Twig_Environment $twigEnv
         */
        do_action( 'wordpresscrm_after_twig_ready', $twigEnv );

        static::$twigEnvironment = $twigEnv;

        return $twigEnv;
    }

    /**
     * Adds global variables to the given environment object.
     *
     * @param \Twig_Environment $twigEnv
     */
    private function addGlobals( \Twig_Environment $twigEnv ) {
        // Access to any entity via {{ entities.logicalName["GUID"] }}
        $twigEnv->addGlobal( 'entities', new Twig\FauxEntitiesCollection() );

        // List of entities ( entityLogicalName => entityDisplayName )
        $twigEnv->addGlobal( 'entities_list', ACRM()->connected()? ACRM()->getMetadata()->getEntitiesList() : [] );

        /**
         * Access to CRM metadata.
         *
         * {{ metadata["contact"].attributes["gendercode"].entityLogicalName }}
         *
         * @see \AlexaCRM\CRMToolkit\Entity\Metadata
         * @see \AlexaCRM\CRMToolkit\Entity\Attribute
         */
        $twigEnv->addGlobal( 'metadata', new Twig\MetadataCollection() );

        // Access to the current record (entity binding)
        $twigEnv->addGlobal( 'currentrecord', ACRM()->getBinding()->getEntity() );

        // `now` global variable
        $twigEnv->addGlobal( 'now', time() );

        $request = ACRM()->request;
        $params = array_merge( $request->cookies->all(), $request->request->all(), $request->query->all() );

        // `params` global variable
        $twigEnv->addGlobal( 'params', $params );
        $twigRequest = [
            'params' => $params,
            'path' => $request->getPathInfo(),
            'path_and_query' => $request->getRequestUri(),
            'query' => $request->getQueryString()? '?' . $request->getQueryString() : '',
            'url' => $request->getUri(),
            'referer' => $request->headers->get( 'referer' ),
        ];

        // `request` global variable
        $twigEnv->addGlobal( 'request', $twigRequest );

        // CRM connection status
        $twigEnv->addGlobal( 'crm', [ 'connected' => ACRM()->connected() ] );

        /**
         * Triggered after default global variables are set up.
         *
         * @param \Twig_Environment $twigEnv
         */
        do_action( 'wordpresscrm_after_twig_globals', $twigEnv );
    }

    /**
     * Adds default filters to the given environment object.
     *
     * @param \Twig_Environment $twigEnv
     */
    private function addFilters( \Twig_Environment $twigEnv ) {
        $addQuery = new \Twig_SimpleFilter( 'add_query', function( $url, $argName, $argValue ) {
            return add_query_arg( $argName, $argValue, $url );
        } );

        $wpautop = new \Twig_SimpleFilter( 'wpautop', function( $value ) {
            return wpautop( $value );
        } );

        $toEntityReference = new \Twig_SimpleFilter( 'toEntityReference', function( $value ) {
            if ( $value instanceof Entity ) {
                return $value->toEntityReference();
            }

            if ( !is_array( $value ) || !array_key_exists( 'LogicalName', $value ) ) {
                return null;
            }

            $ref = new EntityReference( $value['LogicalName'] );
            if ( array_key_exists( 'Id', $value ) ) {
                $ref->Id = $value['Id'];
            }

            if ( array_key_exists( 'DisplayName', $value ) ) {
                $ref->displayName = $value['DisplayName'];
            }

            return $ref;
        } );

        $twigEnv->addFilter( $addQuery );
        $twigEnv->addFilter( $wpautop );
        $twigEnv->addFilter( $toEntityReference );
    }

}
