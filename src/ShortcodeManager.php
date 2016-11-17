<?php

namespace AlexaCRM\WordpressCRM;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Manages shortcodes available via the plugin
 *
 * @package AlexaCRM\WordpressCRM
 */
class ShortcodeManager {

    /**
     * List of supported shortcodes and mapping to respective classes
     *
     * @var array
     */
    protected $shortcodes = [
        'view'  => 'AlexaCRM\\WordpressCRM\\Shortcode\\View',
        'field' => 'AlexaCRM\\WordpressCRM\\Shortcode\\Field',
        'form'  => 'AlexaCRM\\WordpressCRM\\Shortcode\\Form',
    ];

    /**
     * Stores shortcode implementations
     *
     * @var Shortcode[]
     */
    protected $shortcodeProcessors = [ ];

    /**
     * ShortcodeManager constructor.
     *
     * Registers all shortcodes in WordPress
     */
    public function __construct() {
        /**
         * Filters the list of supported shortcodes and their respective implementations
         *
         * Every item key is the non-prefixed name of the shortcode, the value designates
         * a fully qualified class name that extends AlexaCRM\WordpressCRM\Shortcode
         * and implements a public method Shortcode::shortcode()
         *
         * @param array $shortcodes List of supported shortcodes
         */
        $this->shortcodes = apply_filters( 'wordpresscrm_shortcodes', $this->shortcodes );
        ACRM()->log->debug( sprintf( 'Registered %d shortcode handlers.', count( $this->shortcodes ) ), [ 'shortcodes' => array_keys( $this->shortcodes ) ] );

        foreach ( $this->shortcodes as $shortcodeName => $shortcodeClass ) {
            $fullShortcodeName = $this->getFullShortcodeName( $shortcodeName );
            add_shortcode( $fullShortcodeName, [ $this, 'render' ] );
        }
    }

    /**
     * Renders the shortcode
     *
     * @param array $attributes
     * @param string $content
     * @param string $tagName
     *
     * @return string
     */
    public function render( $attributes, $content = null, $tagName ) {
        $shortcodeName = $this->getUnprefixedShortcodeName( $tagName );

        if ( !array_key_exists( $shortcodeName, $this->shortcodeProcessors ) ) {
            $shortcodeClassName                          = $this->shortcodes[ $shortcodeName ];
            $this->shortcodeProcessors[ $shortcodeName ] = new $shortcodeClassName();
        }

        ACRM()->log->info( "Rendering shortcode [{$tagName}]." );
        return $this->shortcodeProcessors[ $shortcodeName ]->shortcode( $attributes, $content, $tagName );
    }

    /**
     * Returns a prefixed shortcode name
     *
     * @param string $shortcodeName
     *
     * @return string
     */
    protected function getFullShortcodeName( $shortcodeName ) {
        return Plugin::PREFIX . $shortcodeName;
    }

    /**
     * Returns a non-prefixed shortcode name
     *
     * @param string $shortcodeName
     *
     * @return string
     */
    protected function getUnprefixedShortcodeName( $shortcodeName ) {
        return str_replace( Plugin::PREFIX, '', $shortcodeName );
    }
}
