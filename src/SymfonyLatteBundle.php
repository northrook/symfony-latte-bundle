<?php

namespace Northrook\Symfony\Latte;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SymfonyLatteBundle extends Bundle
{
	public function getPath() : string {
		return dirname( __DIR__ );
	}
}