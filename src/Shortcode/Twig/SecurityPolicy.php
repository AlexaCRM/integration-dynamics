<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Twig;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Twig\Sandbox\SecurityPolicyInterface;

/**
 * Implements a security policy for twig templates in the plugin.
 */
class SecurityPolicy implements SecurityPolicyInterface {

    public function checkSecurity( $tags, $filters, $functions ): void {
    }

    public function checkMethodAllowed( $obj, $method ): void {
    }

    public function checkPropertyAllowed( $obj, $property ): void {
    }
}
