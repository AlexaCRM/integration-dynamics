<?php

namespace AlexaCRM\WordpressCRM\Shortcode;

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

        static::$twigEnvironment = $twigEnv;

        return $twigEnv;
    }
}
