<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Twig\Nodes;

use Twig_Compiler;

/**
 * Represents the `form` node.
 */
class FormNode extends \Twig_Node {

    /**
     * Compiles the node.
     *
     * @param Twig_Compiler $compiler
     */
    public function compile( Twig_Compiler $compiler ) {
        $compiler->write( "if ( ACRM()->connected() ) {\n")->indent();

        $compiler->write( "\$entityName = " );
        $this->getAttribute( 'entity' )->compile( $compiler );
        $compiler->write( ";\n" );
        if ( $this->hasAttribute( 'name' ) ) {
            $compiler->write( "\$formName = " );
            $this->getAttribute( 'name' )->compile( $compiler );
            $compiler->write( ";\n" );
        } else {
            $compiler->write( "\$formName = null;\n" );
        }

        $extraAttributes = array_diff_key( $this->attributes, [ 'entity' => null, 'name' => null ] );

        $compiler->write( "\$extraAttributes = [" );

        end( $extraAttributes );
        $lastAttributeName = key( $extraAttributes );
        foreach ( $extraAttributes as $attributeName => $attributeValue ) {
            $compiler->string( $attributeName )->write( '=>' );
            $attributeValue->compile( $compiler );

            if ( $attributeName !== $lastAttributeName ) {
                $compiler->write( ',' );
            }
        }

        $compiler->write( "];\n" );

        $compiler->write( "\$formModel = \\AlexaCRM\\WordpressCRM\\Form\\Model::buildModel( \$entityName, \$formName, \$extraAttributes );\n" );
        $compiler->write( "\$formView = \$formModel->buildView();\n" );
        $compiler->write( "\$formSubmission = \$formModel->dispatch();\n");
        $compiler->write( "\$context['form'] = array_merge( \$formView, \$formSubmission );\n" );
        $compiler->write( "\$formModel->hydrateRecord(\$formSubmission['fields']);\n");
        $compiler->subcompile( $this->getNode( 'template' ) );
        $compiler->write( "if(count(\$formView)){\n");
        $compiler->write( "wp_enqueue_script('wordpresscrm-form');\n" );
        $compiler->write( "}\n" );

        $compiler->outdent()->write( "}\n" );
    }

}
