<?php declare ( strict_types = 1 );

namespace Northrook\Symfony\Latte\Nodes;

use Generator;
use Latte\CompileException;
use Latte\Compiler;
use Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Northrook\Elements\Element\Attribute;

/**
 * Parsing `n:id` attributes for the {@see  Compiler\TemplateParser}
 *
 * @copyright David Grudl
 * @see       https://davidgrudl.com  David Grudl
 * @see       https://latte.nette.org Latte Templating Engine
 *
 * @version   1.0 ✅
 * @author    Martin Nielsen <mn@northrook.com>
 *
 * @link      https://github.com/northrook Documentation
 * @todo      Update URL to documentation
 */
final class IdNode extends StatementNode
{
    public ArrayNode $args;

    /**
     * @throws CompileException
     */
    public static function create( Tag $tag ) : IdNode {

        if ( $tag->htmlElement->getAttribute( 'id' ) ) {
            throw new CompileException( 'It is not possible to combine id with n:id.', $tag->position );
        }

        if ( !class_exists( Attribute::class ) ) {
            throw new CompileException(
                'Latte tag `n:id` requires the ' . Attribute::class . '::class to be present.',
            );
        }


        $node       = new IdNode();
        $node->args = $tag->parser->parseArguments();

        return $node;
    }

    public function print( PrintContext $context ) : string {
        return $context->format(
            'echo ($ʟ_tmp = array_filter(%node)) ? \' id="\' . Northrook\Elements\Element\Attribute::id(implode(" ", array_unique($ʟ_tmp))) . \'"\' : "" %line;',
            $this->args,
            $this->position,
        );
    }

    public function &getIterator() : Generator {
        yield $this->args;
    }
}