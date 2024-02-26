<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare ( strict_types = 1 );

namespace Northrook\Symfony\Latte\Nodes;

use Latte\CompileException;
use Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * n:id="..."
 */
final class IdNode extends StatementNode
{
	public ArrayNode $args;

	public static function create( Tag $tag ) : IdNode {

		if ( $tag->htmlElement->getAttribute( 'id' ) ) {
			throw new CompileException( 'It is not possible to combine id with n:id.', $tag->position );
		}

//		$tag->expectArguments();
		$node = new IdNode();
		$node->args = $tag->parser->parseArguments();

		return $node;
	}

	public function print( PrintContext $context ) : string {
		return $context->format(
			'echo ($ʟ_tmp = array_filter(%node)) ? \' id="\' . Northrook\Support\HTML\Element::id(implode(" ", array_unique($ʟ_tmp))) . \'"\' : "" %line;',
			// 'echo ($ʟ_tmp = array_filter(%node)) ? \' id="\' . LR\Filters::escapeHtmlAttr(implode(" ", array_unique($ʟ_tmp))) . \'"\' : "" %line;',
			$this->args,
			$this->position,
		);
	}

	public function &getIterator() : \Generator {
		yield $this->args;
	}
}
