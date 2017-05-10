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
        $compiler->write( "\$entityName = " );
        $this->getAttribute( 'entity' )->compile( $compiler );
        $compiler->write( ";\n" );
        $compiler->write( "\$formName = " );
        $this->getAttribute( 'name' )->compile( $compiler );
        $compiler->write( ";\n" );

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
        $compiler->write( "\$context['form'] = \$formModel->buildView();\n" );
        $compiler->write( "\$context['validationErrors'] = apply_filters('wordpresscrm_form_validation', null);\n" );
        $compiler->write( "\$bouncedFields = apply_filters('wordpresscrm_form_bounced_fields', []);\n" );
        $compiler->write( "\$formModel->hydrateRecord(\$bouncedFields);\n");
        $compiler->subcompile( $this->getNode( 'template' ) );
        $compiler->write( "wp_enqueue_script('wordpresscrm-form');\n" );
        $compiler->write( "\$formModel->registerHandler();\n" );
    }

}
