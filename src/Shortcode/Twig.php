<?php

namespace AlexaCRM\WordpressCRM\Shortcode;

use AlexaCRM\CRMToolkit\Entity\EntityReference;
use AlexaCRM\WordpressCRM\Shortcode;
use Twig_Extension_Debug;

/**
 * Implements the Twig templates with entity/user binding,
 * views, forms, etc.
 */
class Twig extends Shortcode {

    /**
     * @var \Twig_Environment
     */
    private static $twigEnvironment;

    /**
     * Array loader that stores inline shortcode templates.
     *
     * @var \Twig_Loader_Array
     */
    private static $shortcodeLoader;

    /**
     * Renders the shortcode.
     *
     * @param array $attributes
     * @param string $content
     * @param $tagName
     *
     * @return string
     */
    public function shortcode( $attributes, $content = null, $tagName ) {
        $twig = $this->getTwig();

        // Remove wpautop() side effects
        $content = trim( static::reverse_wpautop( $content ) );

        $templateKey = 'template_' . sha1( $content );
        static::$shortcodeLoader->setTemplate( $templateKey, $content );

        $output = $twig->render( $templateKey );

        // Reapply wpautop()
        return $output;
    }

    /**
     * Gives access to the Twig engine instance.
     *
     * @return \Twig_Environment
     */
    private function getTwig() {
        if ( static::$twigEnvironment instanceof \Twig_Environment ) {
            return static::$twigEnvironment;
        }

        $chainLoader = new \Twig_Loader_Chain();

        static::$shortcodeLoader = new \Twig_Loader_Array();
        $chainLoader->addLoader( static::$shortcodeLoader );
        $chainLoader->addLoader(
            new \Twig_Loader_Filesystem( WORDPRESSCRM_DIR . '/templates/twig' )
        );

        $isDebugEnabled = defined( 'WP_DEBUG' ) && WP_DEBUG;

        $twigEnv = new \Twig_Environment( $chainLoader, [ 'debug' => $isDebugEnabled ] );
        if ( $isDebugEnabled ) {
            $twigEnv->addExtension( new Twig_Extension_Debug() );
        }

        // Add global variables to the context
        $this->addGlobals( $twigEnv );

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
        ];

        // `request` global variable
        $twigEnv->addGlobal( 'request', $twigRequest );

        /**
         * Triggered after default global variables are set up.
         *
         * @param \Twig_Environment $twigEnv
         */
        do_action( 'wordpresscrm_after_twig_globals', $twigEnv );
    }
}
