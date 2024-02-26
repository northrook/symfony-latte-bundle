<?php

namespace Northrook\Symfony\Latte;

use Latte;
use Symfony\Component\Stopwatch\Stopwatch;

final class Extension extends Latte\Extension
{

	private array $extensions = [];

	public function __construct(
		private Stopwatch $stopwatch
	) {

	}

}