<?php

namespace Northrook\Symfony\Latte;

use Latte;
use Latte\Runtime\Template;
use Northrook\Support\Debug;
use Northrook\Symfony\Latte\Nodes\ClassNode;
use Northrook\Symfony\Latte\Nodes\IdNode;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

	/**
	 * Initializes before template is rendered.
	 */
	public function beforeRender( Template $template ) : void {
		// dd( $template );
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
			'route'        => [ $this, 'getRoute' ],
			'encoded_href' => static function ( $string ) {
				echo CoreExtension::encodeHref( $string );
			},
			'debug'        => [ Debug::class, 'log' ],
			'dump_logs'    => [ Debug::class, 'dumpLogs' ],
			'print_debug'  => static function ( ...$args ) {
				echo '<pre>';
				foreach ( $args as $arg ) {
					print_r( $arg );
				}
				echo '</pre>';
			},
			'var_dump'     => static function ( ...$args ) {
				var_dump( ... $args );
			},
			'dump'         => static fn ( ...$args ) => dump( $args ),
			'dd'           => static fn ( ...$args ) => dd( $args ),

//			'asset'      => [$this->asset, 'asset'],
//			'icon'       => [Get::class, 'icon'],
//			'avatar'     => [RenderComponents::class, 'avatar'],
//			'menu'       => [RenderComponents::class, 'menu'],
		];
	}

	public function getFilters() : array {
		return [
			'echo'   => static function ( $string ) {
				echo $string;
			},
			'encode' => static function ( $string ) {
				echo CoreExtension::encodeString( $string );
			},
		];
	}

	public function getRoute( string $name ) : string {
		try {
			return $this->url->generate( $name );
		}
		catch (
		InvalidParameterException |
		MissingMandatoryParametersException |
		RouteNotFoundException  $e
		) {
			$this->logger?->error(
				message : "Unable to resolve route {name}",
				context : [
					          'name'      => $name,
					          'exception' => $e,
				          ],
			);
			return $name;
		}

	}


	public static function encodeHref( string $href ) : string {
		return 'href="" data-href="' . base64_encode( $href ) . '"';
	}

	public static function encodeString( string $string ) : string {
		return '<data value="' . base64_encode( $string ) . '"></data>';
	}
}