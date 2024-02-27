<?php

namespace Northrook\Symfony\Latte;

/**
 * @package App\Latte
 * */
final class Template extends AbstractTemplate {



	public function __construct(
	) {
		$this->get = Environment::getGlobalParameter();
	}
}