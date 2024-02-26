<?php

namespace Northrook\Symfony\Latte;

use Latte;
use Northrook\Symfony\Latte\Nodes\ClassNode;
use Northrook\Symfony\Latte\Nodes\IdNode;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class CoreExtension extends Latte\Extension
{
	public function __construct(
		private ?UrlGeneratorInterface $url = null,
		private ?LoggerInterface      $logger = null,
	) {
		if ( $this->url === null ) {
			throw new \RuntimeException( 'UrlGeneratorInterface is required' );
		}
	}

	public function getTags() : array {
		return [
			'n:id'    => [ IdNode::class, 'create' ],
			'n:class' => [ ClassNode::class, 'create' ],
		];
	}

	public function getFunctions() : array {
		return [
			'route' => [ $this, 'getRoute' ],
		];
	}

	public function getRoute( string $name ) : string {
		try {
			return $this->url->generate( $name );
		}
		catch ( InvalidParameterException | MissingMandatoryParametersException | RouteNotFoundException  $e ) {
			$this->logger?->error( "Unable to resolve route {name}",  [
				'name' => $name,
				'exception' => $e
			] );
			return $name;
		}

	}

	public static function getSubscribedServices() : array {
		return [

		];
	}
}