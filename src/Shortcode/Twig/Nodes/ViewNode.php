<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Twig\Nodes;

use Twig_Compiler;

/**
 * Represents the `view` node.
 */
class ViewNode extends \Twig_Node {

    /**
     * ViewNode constructor.
     *
     * @param array $nodes
     * @param array $attributes
     * @param int $lineno
     * @param null $tag
     */
    public function __construct( array $nodes = [], array $attributes = [], $lineno = 0, $tag = null ) {
        parent::__construct( $nodes, $attributes, $lineno, $tag );
    }

    /**
     * Compiles the node.
     *
     * @param Twig_Compiler $compiler
     */
    public function compile( Twig_Compiler $compiler ) {
        $compiler->write( "if ( ACRM()->connected() ) {\n")->indent();

        $compiler->write( "\$viewAttributes = [" );
        end( $this->attributes );
        $lastAttributeName = key( $this->attributes );
        foreach ( $this->attributes as $attributeName => $attribute ) {
            $compiler->write( "'{$attributeName}'=>");
            $attribute->compile( $compiler );

            if ( $lastAttributeName !== $attributeName ) {
                $compiler->write( ',' );
            }
        }
        $compiler->write( "];\n" );

        $compiler->write( "\$viewBuilder = new \\AlexaCRM\\WordpressCRM\\ViewBuilder(\$viewAttributes);\n" );
        $compiler->write( "\$context['entityview'] = \$viewBuilder->build();\n" );
        $compiler->subcompile( $this->getNode( 'template' ) );

        $compiler->outdent()->write( "}\n" );
    }

}
