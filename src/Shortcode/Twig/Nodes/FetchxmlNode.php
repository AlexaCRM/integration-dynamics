<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Twig\Nodes;

use Twig_Compiler;
use Twig_Node;

/**
 * Represents the `fetchxml` tag node.
 */
class FetchxmlNode extends Twig_Node {

    /**
     * FetchxmlNode constructor.
     *
     * @param Twig_Node $fetchxmlNode
     * @param array $attributes
     * @param int $lineNo
     * @param null $tag
     */
    public function __construct( $fetchxmlNode = null, array $attributes = [], $lineNo = 0, $tag = null ) {
        parent::__construct( [ 'fetchxml' => $fetchxmlNode ], $attributes, $lineNo, $tag );
    }

    /**
     * Compiles the node.
     *
     * @param Twig_Compiler $compiler
     */
    public function compile( Twig_Compiler $compiler ) {
        $compiler->write( "ob_start();\n" );
        $compiler->subcompile( $this->getNode( 'fetchxml' ) );
        $compiler->write( "\$fetchxml = trim(ob_get_clean());\n" );

        $compiler->write( "if(ACRM()->connected() && \$fetchxml !== '') {\n" );
        $compiler->indent();
        $compiler->write( "\$exception = null;\n");

        if ( $this->hasAttribute( 'cache' ) ) {
            $compiler->write( "\$cache = ACRM()->getCache();\n" );
            $compiler->write( "\$cacheKey = 'wpcrm_twigdata_' . sha1(\$fetchxml);\n" );
            $compiler->write( "\$records = \$cache->get(\$cacheKey);\n" );
            $compiler->write( "if(\$records === null){\n" );
            $compiler->indent();
        }

        $compiler->write( "try {\n" )
            ->indent()
                ->write( "\$records = ASDK()->retrieveMultiple(\$fetchxml);\n")
            ->outdent()
            ->write( "} catch ( \\Exception \$e ) {\n" )
            ->indent()
                ->write( "\$exception = \$e;\n" )
            ->outdent()
            ->write( "}\n" );

        if ( $this->hasAttribute( 'cache' ) ) {
            $interval = new \DateInterval( $this->getAttribute( 'cache' ) );
            $seconds = $interval->y * YEAR_IN_SECONDS + $interval->m * MONTH_IN_SECONDS + $interval->d * DAY_IN_SECONDS + $interval->h * HOUR_IN_SECONDS + $interval->i * MINUTE_IN_SECONDS + $interval->s;
            $compiler->write( "\$cache->set(\$cacheKey, \$records, {$seconds});\n" );
            $compiler->outdent();
            $compiler->write( "}\n" );
        }
        $compiler->write( "\$context['{$this->getAttribute( 'collection' )}'] = " );
        $compiler->write( "[ 'xml'=>\$fetchxml, 'results'=>['entities'=>(\$records? \$records->Entities : null)," );
        $compiler->write( "'total_record_count'=>(\$records? \$records->TotalRecordCount : null), 'more_records'=>(\$records? \$records->MoreRecords: null)," );
        $compiler->write( "'paging_cookie'=>(\$records? \$records->PagingCookie: null)]," );
        $compiler->write( "'error'=>(\$exception?\$exception->getMessage():null)];\n" );
        $compiler->outdent();
        $compiler->write( "}\n");
    }

}
