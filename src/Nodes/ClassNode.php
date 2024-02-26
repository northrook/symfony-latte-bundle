<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare ( strict_types = 1 );

namespace App\framework\Latte\Nodes;

use Latte\CompileException;
use Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * class="..."
 */
final class ClassNode extends StatementNode {
    public ArrayNode $args;

    public static function create( Tag $tag ): ClassNode {

        if ( $tag->htmlElement->getAttribute( 'n:class' ) ) {
            throw new CompileException( 'It is not possible to combine id with n:class, or class.', $tag->position );
        }

        $tag->expectArguments();
        $node       = new ClassNode;
        $node->args = $tag->parser->parseArguments();

        return $node;
    }

    public function print( PrintContext $context ): string {
        // var_dump( $context );

        return $context->format(
            'echo ($ʟ_tmp = array_filter(%node)) ? \' class="\' . Northrook\Support\HTML\Element::classes(implode(" ", array_unique($ʟ_tmp))) . \'"\' : "" %line;',
            // 'echo ($ʟ_tmp = array_filter(%node)) ? \' id="\' . LR\Filters::escapeHtmlAttr(implode(" ", array_unique($ʟ_tmp))) . \'"\' : "" %line;',
            $this->args,
            $this->position,
        );
    }

    public function &getIterator(): \Generator
    {
        yield $this->args;
    }
}
