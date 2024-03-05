<?php

namespace Northrook\Symfony\Latte;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @version 1.0 ☑️
 * @author Martin Nielsen <mn@northrook.com>
 *
 * @link https://github.com/northrook Documentation
 * @todo Update URL to documentation : root of symfony-latte-bundle
 */
final class SymfonyLatteBundle extends Bundle
{
	public function getPath() : string {
		return dirname( __DIR__ );
	}
}