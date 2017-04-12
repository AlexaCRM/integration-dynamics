<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Twig\Nodes;

use Twig_Compiler;

class FetchxmlNode extends \Twig_Node {

    public function __construct( $fetchxmlNode = null, array $attributes = [], $lineNo = 0, $tag = null ) {
        parent::__construct( [ 'fetchxml' => $fetchxmlNode ], $attributes, $lineNo, $tag );
    }

    public function compile( Twig_Compiler $compiler ) {
        $compiler->write( "ob_start();\n" );
        $compiler->subcompile( $this->getNode( 'fetchxml' ) );
        $compiler->write( "\$fetchxml = trim(ob_get_clean());\n" );

        $compiler->write( "if(ACRM()->connected() && \$fetchxml !== '') {\n" );
        $compiler->indent();
        $compiler->write( "\$records = ASDK()->retrieveMultiple(\$fetchxml);\n");
        $compiler->write( "\$context['{$this->getAttribute( 'collection' )}'] = \$records->Entities;\n" );
        $compiler->outdent();
        $compiler->write( "}\n");
    }

}
