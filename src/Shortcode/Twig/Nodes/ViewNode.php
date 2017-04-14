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
        /**
         * @var \Twig_Node_Expression_Constant $entityName
         */
        $entityName = $this->getAttribute( 'entity' );
        $viewName = $this->getAttribute( 'name' );

        $compiler->write( "\$entityName = " );
        $entityName->compile( $compiler );
        $compiler->write( ";\n" );
        $compiler->write( "\$viewName = " );
        $viewName->compile( $compiler );
        $compiler->write( ";\n" );

        // Add parameters
        $compiler->write( "\$viewParameters = [];\n" );
        if ( $this->hasAttribute( 'parameters' ) ) {
            $viewParameters = $this->getAttribute( 'parameters' );
            $compiler->write( "\$viewParameters = " );
            $viewParameters->compile( $compiler );
            $compiler->write( ";\n" );
        }

        // Add lookups
        $compiler->write( "\$viewLookups = [];\n" );
        if ( $this->hasAttribute( 'lookups' ) ) {
            $viewLookups = $this->getAttribute( 'lookups' );
            $compiler->write( "\$viewLookups = " );
            $viewLookups->compile( $compiler );
            $compiler->write( ";\n" );
        }

        $compiler
            ->write( "\$view = \\AlexaCRM\\WordpressCRM\\View::getViewForEntity(\$entityName, \$viewName);\n")
            ->write( "if(\$view === null) {\n" )
            ->indent()
                ->write( "throw new \\Exception('Specified view not found');\n" )
            ->outdent()
            ->write( "}\n" );

        $compiler->write( "list( \$fetchxml, \$layoutxml ) = [ \$view->fetchxml, \$view->layoutxml ];\n" );

        // Substitute parameters
        $compiler
            ->write( "\$fetchxml = \\AlexaCRM\\WordpressCRM\\FetchXML::replacePlaceholderValuesByParametersArray(")
            ->write( "\$fetchxml, \$viewParameters);\n" );

        // Substitute lookups
        $compiler
            ->write( "\$fetchxml = \\AlexaCRM\\WordpressCRM\\FetchXML::replaceLookupConditionsByLookupsArray(" )
            ->write( "\$fetchxml, \$viewLookups);\n");

        // Retrieve records
        if ( $this->hasAttribute( 'cache' ) ) {
            $compiler->write( "\$cache = ACRM()->getCache();\n" );
            $compiler->write( "\$cacheKey = 'wpcrm_twigdata_' . sha1(\$fetchxml);\n" );
            $compiler->write( "\$retrieveResult = \$cache->get(\$cacheKey);\n" );
            $compiler->write( "if(\$retrieveResult === null){\n" );
            $compiler->indent();
        }
        $compiler->write( "\$retrieveResult = ASDK()->retrieveMultiple(\$fetchxml);\n");

        if ( $this->hasAttribute( 'cache' ) ) {
            $intervalNode = $this->getAttribute( 'cache' );
            $compiler->write( "\$interval = new \\DateInterval(" );
            $intervalNode->compile( $compiler );
            $compiler->write( ");\n" );
            $compiler->write( "\$seconds = \$interval->y * YEAR_IN_SECONDS + \$interval->m * MONTH_IN_SECONDS + \$interval->d * DAY_IN_SECONDS + \$interval->h * HOUR_IN_SECONDS + \$interval->i * MINUTE_IN_SECONDS + \$interval->s;" );
            $compiler->write( "\$cache->set(\$cacheKey, \$retrieveResult, \$seconds);\n" );
            $compiler->outdent();
            $compiler->write( "}\n" );
        }

        $compiler->write( "\$viewLayout = new SimpleXMLElement(\$layoutxml);\n" );
        $compiler->write( "\$rawCells  = \$viewLayout->xpath( './/cell' );\n");
        $compiler->write( "\$viewCells = [];\n" );
        $compiler->write( "foreach ( \$rawCells as \$cell ) {" )
            ->indent()
            ->write( "\$viewCells[(string)\$cell['name']] = \$cell;\n" )
            ->outdent()
            ->write( "}\n" );

        $compiler->write( "\$viewRows = \\AlexaCRM\\WordpressCRM\\View::getViewRows(\$retrieveResult," )
            ->write( "\$viewCells,\$fetchxml);\n" );

        $compiler->write( "\$context['_columns'] = [];\n" );
        $compiler->write( "foreach ( reset(\$viewRows) as \$columnName => \$column ) {" )
            ->indent()
            ->write( "\$context['_columns'][\$columnName] = \$column['head'];\n" )
            ->outdent()
            ->write( "}\n" );

        $compiler->write( "\$context['_records'] = \$viewRows;\n" );
        $compiler->subcompile( $this->getNode( 'template' ) );

        $compiler->write( "unset(\$context['_columns'], \$context['_records']);\n");
    }

}
