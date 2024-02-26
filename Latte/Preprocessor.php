<?php

namespace Northrook\Symfony\Latte;

use Northrook\Support\Attribute\EntryPoint;
use Northrook\Support\Str;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Log\Logger;
use Symfony\Component\Stopwatch\Stopwatch;
use function print_r;

abstract class Preprocessor
{

	protected string $content;

	public bool $comments                  = false;
	public bool $minify                    = false;
	public bool $preserveExcessWhitespaces = false;


	#[EntryPoint]
	final public function __construct(
		string $content,
		protected ?LoggerInterface $logger = null,
		protected ?Stopwatch $stopwatch = null
	) {}

	abstract public function construct() : void;

	final public function getContent() : string {
		$this->prepareContent();
		$this->construct();

		return $this->content;
	}


	final protected function updateContent( string $match, string $update ): void {
		$this->content = str_ireplace( $match, $update, $this->content );
	}

	private function prepareContent() : void {


		// Remove Latte comments
		// TODO: Remove all comments?
		$this->content = preg_replace(
			       '/{\*.*?\*}/ms',
			       '',
			       $this->content,
			count: $count
		);

		$this->logger->info( 'Removed {count} Latte comments', [ 'count' => $count ] );


		$this->content = preg_replace_callback(
			'/{(.*?)}/ms',
			function ( array $m ) {
				$var = trim( $m[1] );
				if ( ! str_contains( $var, '$' ) || Str::startsWith( $var, ['layout', 'block', 'var'] ) ) {
					return '{' . $var . '}';
				}

				if ( ! str_contains( $var, '??' ) ) {
					$test = $var;
//					print_r($test);
					$var = '{' . $var .= '??false}';
					// dump( $m,$var );

					return $var;
				}

				// dump( $m,$var );
				$var = '{' . $var . '}';


				return $var;
			},
			$this->content
		);



		if ( $this->minify === false && $this->preserveExcessWhitespaces === false ) {

			$this->content = preg_replace(
				'/^.\s*\n/ms',
				"\n",
				$this->content,
			);

		}

		if ( $this->minify ) {
			$this->content = Str::squish( $this->content );
			// dd( $this->content );
		}

	}

}