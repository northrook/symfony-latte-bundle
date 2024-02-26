<?php

namespace Northrook\Symfony\Latte;

use Latte;
use Northrook\Symfony\Latte\Nodes\ClassNode;
use Northrook\Symfony\Latte\Nodes\IdNode;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class CoreExtension extends Latte\Extension
{
	public function __construct(
		private readonly ?UrlGeneratorInterface $url = null,
		private readonly ?LoggerInterface       $logger = null,
	) {
		if ( $this->url === null ) {
			throw new RuntimeException( 'UrlGeneratorInterface is required' );
		}
	}

	public function getTags() : array {
		return [
			'n:id'    => [ IdNode::class, 'create' ],
			'n:class' => [ ClassNode::class, 'create' ],
//			'n:href'  => [ HrefNode::class, 'create' ], // TODO: Implement
//			'asset'   => [$this->asset, 'asset'], // TODO: Should be part of n:href? Can we autocomplete assets (images)?
//			'n:src'   => [$this->asset, 'asset'], // TODO: Can we make this autocomplete, and in <img only?
												  //       If no image, provide optional default
		];
	}

	public function getFunctions() : array {
		return [
			'route' => [ $this, 'getRoute' ],
		];
	}

	public function getFilters(): array {
		return [
			'echo' => static function ( $string ) {
				echo $string;
			},
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
}