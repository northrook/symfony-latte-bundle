<?php

namespace Northrook\Symfony\Latte;

use App\framework\Latte\Nodes\IdNode;
use Latte;

final class CoreExtension extends Latte\Extension
{

	public function getTags(): array {
		return [
			'n:id' => [IdNode::class, 'create'],
		];
	}

}