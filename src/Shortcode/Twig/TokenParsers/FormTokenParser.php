<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Twig\TokenParsers;

use AlexaCRM\WordpressCRM\Shortcode\Twig\Nodes\FormNode;
use Twig_Error_Syntax;
use Twig_NodeInterface;
use Twig_Token;

/**
 * Parses the `form` token.
 */
class FormTokenParser extends \Twig_TokenParser {

    /**
     * Parses a token and returns a node.
     *
     * @return Twig_NodeInterface
     *
     * @throws Twig_Error_Syntax
     */
    public function parse( Twig_Token $token ) {
        $parser = $this->parser;
        $stream = $parser->getStream();
        $lineNo = $token->getLine();

        $arguments = [];

        while( !$stream->test( Twig_Token::BLOCK_END_TYPE ) ) {
            if ( !$stream->test( Twig_Token::NAME_TYPE ) ) {
                $stream->next();
                continue;
            }

            $argName = $stream->expect( Twig_Token::NAME_TYPE )->getValue();
            $stream->expect( Twig_Token::OPERATOR_TYPE, '=' );
            $argValue = $parser->getExpressionParser()->parsePrimaryExpression();
            $arguments[$argName] = $argValue;
        }

        if ( count( array_intersect( [ 'entity', 'name' ], array_keys( $arguments ) ) ) !== 2 ) {
            throw new Twig_Error_Syntax( 'Form must have `entity` and `name` arguments set', $lineNo );
        }

        $stream->expect( Twig_Token::BLOCK_END_TYPE );
        $template = $parser->subparse( [ $this, 'decideFormEnd' ] );

        if ( get_class( $template ) === 'Twig_Node' && $template->getNodeTag() === null ) {
            $stream->injectTokens( [
                new Twig_Token( Twig_Token::BLOCK_START_TYPE, '', $lineNo ),
                new Twig_Token( Twig_Token::NAME_TYPE, 'embed', $lineNo ),
                new Twig_Token( Twig_Token::STRING_TYPE, 'form.twig', $lineNo ),
                new Twig_Token( Twig_Token::BLOCK_END_TYPE, '', $lineNo ),
                new Twig_Token( Twig_Token::BLOCK_START_TYPE, '', $lineNo ),
                new Twig_Token( Twig_Token::NAME_TYPE, 'endembed', $lineNo ),
                new Twig_Token( Twig_Token::BLOCK_END_TYPE, '', $lineNo ),
                new Twig_Token( Twig_Token::BLOCK_START_TYPE, '', $lineNo ),
            ] );
            $template = $parser->subparse( [ $this, 'decideFormEnd'] );
        }

        $stream->expect( Twig_Token::NAME_TYPE, 'endform' );
        $stream->expect( Twig_Token::BLOCK_END_TYPE );

        return new FormNode( [ 'template' => $template ], $arguments, $lineNo );
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag() {
        return 'form';
    }

    /**
     * Tells whether the current node is `endform`.
     *
     * @param Twig_Token $token
     *
     * @return bool
     */
    public function decideFormEnd( Twig_Token $token ) {
        return $token->test( 'endform' );
    }
}
