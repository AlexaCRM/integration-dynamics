<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Twig;

/**
 * Base class for token parsers in the plugin.
 */
abstract class TokenParser extends \Twig_TokenParser {

    /**
     * Tells whether given template is empty - no nodes or only spaces/new lines/etc.
     *
     * @param \Twig_Node $template
     *
     * @return bool
     */
    protected function isEmptyTemplate( \Twig_Node $template ) {
        $allowedNodes = [
            'Twig_Node', 'Twig_Node_Text', 'Twig\Node\Node', 'Twig\Node\TextNode',
        ];

        return in_array( get_class( $template ), $allowedNodes, true )
               && !$template->count()
               && ( !$template->hasAttribute( 'data' ) || trim( $template->getAttribute( 'data' ) ) === '' );
    }

}
