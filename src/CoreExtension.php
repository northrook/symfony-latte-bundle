<?php

namespace Northrook\Symfony\Latte;

use Latte;
use Northrook\Symfony\Latte\Nodes\IdNode;

final class CoreExtension extends Latte\Extension
{

	public function getTags(): array {
		return [
			'n:id' => [IdNode::class, 'create'],
		];
	}

}