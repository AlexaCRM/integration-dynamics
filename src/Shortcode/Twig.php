<?php

namespace AlexaCRM\WordpressCRM\Shortcode;

use AlexaCRM\CRMToolkit\Entity\EntityReference;
use AlexaCRM\WordpressCRM\Shortcode;

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
        return wptexturize( wpautop( $output ) );
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

        $twigEnv = new \Twig_Environment( $chainLoader );

        // Access to any entity via {{ entities.logicalName["GUID"] }}
        $twigEnv->addGlobal( 'entities', new Twig\FauxEntitiesCollection() );

        // Access to the current record (entity binding)
        $twigEnv->addGlobal( 'currentrecord', ACRM()->getBinding()->getEntity() );

        // `fetchxml` tag
        $twigEnv->addTokenParser( new Twig\TokenParsers\FetchxmlTokenParser() );

        // `view` tag
        $twigEnv->addTokenParser( new Twig\TokenParsers\ViewTokenParser() );

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
}
