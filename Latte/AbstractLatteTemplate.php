<?php

namespace Northrook\Symfony\Latte;

use Northrook\Symfony\Core\Services\EnvironmentService;
use Northrook\Symfony\Latte\Parameters\GlobalParameters;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class AbstractLatteTemplate implements ServiceSubscriberInterface {

	/** @var ContainerInterface */
	protected ContainerInterface $container;

	public ?EnvironmentService $env = null;
	public ?GlobalParameters $get = null;

	/** Runs on container initialization.
	 *
	 * * Modified from the Symfony AbstractController
	 * * Adds `environment_service` to the container
	 */
	#[Required]
	public function setContainer( ContainerInterface $container ): ?ContainerInterface {
		$previous        = $this->container ?? null;
		$this->container = $container;

		if ( $this->container->has( 'environment_service' ) ) {
			$this->env = $this->container->get( 'environment_service' );
		}
		if ( $this->container->has( 'global_parameters' ) ) {
			$this->get = $this->container->get( 'global_parameters' );
		}

		return $previous;
	}

	public static function getSubscribedServices(): array {
		return [
			'environment_service' => '?' . EnvironmentService::class,
			'global_parameters'   => '?' . GlobalParameters::class,
		];
	}

}