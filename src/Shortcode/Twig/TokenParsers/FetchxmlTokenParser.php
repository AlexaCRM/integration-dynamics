<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Twig\TokenParsers;

use AlexaCRM\WordpressCRM\Shortcode\Twig\Nodes\FetchxmlNode;
use AlexaCRM\WordpressCRM\Shortcode\Twig\TokenParser;
use Twig_NodeInterface;
use Twig_Token;

/**
 * Implements token parser for the `fetchxml` tag.
 */
class FetchxmlTokenParser extends TokenParser {

    const TAG_BEGIN = 'fetchxml';
    const TAG_END = 'endfetchxml';

    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token
     *
     * @return Twig_NodeInterface
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
            $argValue = $stream->expect( Twig_Token::STRING_TYPE )->getValue();
            $arguments[$argName] = $argValue;
        }

        $stream->expect( Twig_Token::BLOCK_END_TYPE );
        $fetchxml = $parser->subparse( [ $this, 'decideFetchxmlEnd' ] );

        $stream->expect( Twig_Token::NAME_TYPE, static::TAG_END );
        $stream->expect( Twig_Token::BLOCK_END_TYPE );

        return new FetchxmlNode( $fetchxml, $arguments, $lineNo );
    }

    /**
     * Tells whether the current node is `endfetchxml`.
     *
     * @param Twig_Token $token
     *
     * @return bool
     */
    public function decideFetchxmlEnd( Twig_Token $token ) {
        return $token->test( static::TAG_END );
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag() {
        return static::TAG_BEGIN;
    }
}
