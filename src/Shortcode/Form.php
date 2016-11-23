<?php

// Exit if accessed directly
namespace AlexaCRM\WordpressCRM\Shortcode;

use AlexaCRM\WordpressCRM\Shortcode;
use AlexaCRM\WordpressCRM\Shortcode\Form\FormInstance;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Form shortcode [msdyncrm_form]
 *
 * @package AlexaCRM\WordpressCRM\Shortcode
 */
class Form extends Shortcode {

    /**
     * Form constructor.
     */
    public function __construct() {
        add_shortcode( 'msdyncrm_form', array( $this, 'shortcode' ) );

        add_action( 'wp_ajax_wordpresscrm_ajax_form', array( &$this, 'processAjaxForm' ) );
        add_action( 'wp_ajax_nopriv_wordpresscrm_ajax_form', array( &$this, 'processAjaxForm' ) );
    }

    /**
     * Ajax form handler
     */
    public function processAjaxForm() {
        if ( isset( $_POST ) && isset( $_POST["atts"] ) ) {
            $atts = (array) json_decode( htmlspecialchars_decode( $_POST["atts"] ) );

            echo $this->shortcode( $atts, true );
        }

        exit();
    }

    /**
     * Shortcode handler
     *
     * @param array $attributes
     * @param string $content
     * @param string $tagName
     *
     * @return string
     */
    public function shortcode( $attributes, $content = null, $tagName ) {
        $form = new FormInstance();

        return $form->shortcode( $attributes );
    }

}
