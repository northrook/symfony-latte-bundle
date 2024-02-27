<?php

/** @noinspection PhpUnused */

declare( strict_types = 1 );


namespace Northrook\Symfony\Latte;

use Latte;
use Latte\Engine;
use Latte\Extension;
use Northrook\Support\Attribute\EntryPoint;
use Northrook\Support\File;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * * AbstractCoreController
 *
 */
#[EntryPoint] // Called by the AbstractCoreController
class Environment
{

	private ?Latte\Engine $latte = null;

	private static array $templates = [];

	/** @var Extension[] */
	private array $extensions = [];

	/** @var Preprocessor[] */
	private array $preprocessors = [];

	public function __construct(
		public string              $templateDirectory,
		public string              $cacheDirectory,
		protected ?CoreExtension   $coreExtension = null,
		protected ?LoggerInterface $logger = null,
		protected ?Stopwatch       $stopwatch = null,
	) {}


	/** Render '$template' to string
	 *
	 * * Accepts a path to a template file or a template string.
	 * * Parses `$parameters` into Latte variables.
	 *
	 * @param  string  $template
	 * @param  object|array  $parameters
	 * @return string
	 */
	public function render( string $template, object | array $parameters = [] ) : string {

		$this->latte ??= $this->startEngine();

		$render = $this->latte->renderToString(
			$this->templateFilePath( $template ),
			$this->templateParameters( $parameters ),
		);

		$this->stopwatch?->stop( 'engine' );

		return $render;
	}

	public static function addTemplate( array $array ) : void {
		foreach ( $array as $name => $template ) {
			self::$templates[ strtolower( $name ) ] = $template;
		}
	}

	public static function parameters( array $parameters ) : array {

		foreach ( $parameters as $key => $value ) {
			if ( is_array( $value ) ) {
				$parameters[ $key ] = (object) self::parameters( $value );
			}
		}

		return $parameters;
	}

	public function addPrecompiler( Preprocessor ...$preprocessor ) : self {

		$this->preprocessors = array_merge( $this->preprocessors, $preprocessor );

		return $this;
	}

	public function addExtension( Extension ...$extension ) : self {

		$this->extensions = array_merge( $this->extensions, $extension );

		return $this;
	}

	/** Start the Latte engine.
	 *
	 * * Automatically started when $this->render() is called.
	 * * Can be manually started with $this->startEngine().
	 * * Engine is stored in `$this->latte`.
	 * * If called when already started, it will just return {@see Engine}.
	 *
	 * Actions:
	 * * Start the Latte engine, stored in `$this->latte`
	 * * Adds the {@see CoreExtension} to the `$this->extensions` array
	 * * Adds all extensions from `$this->extensions`
	 * * Sets the template directory to `$this->templateDirectory`
	 * * Sets the cache directory to `$this->cacheDirectory`
	 * * Sets the Loader to a custom Latte {@see Loader}
	 * * Passes registered {@see Preprocessor} and {@see self::$templates} to the {@see Loader}
	 * * Passes the {@see LoggerInterface} to the {@see Loader}
	 *
	 *
	 * @param  Engine|null  $engine  The engine to use, defaults to Latte
	 * @param  mixed  ...$args  Arguments to pass to the custom engine
	 * @return Engine
	 */
	public function startEngine( ?Latte\Engine $engine = null, mixed ...$args ) : Latte\Engine {

		if ( $this->latte ) {
			return $this->latte;
		}

		$this->stopwatch?->start('engine', 'latte' );

		$this->latte = new $engine( ... $args ) ?? new Latte\Engine();

		$this->addExtension( $this->coreExtension ?? new CoreExtension() );

		foreach ( $this->extensions as $extension ) {
			$this->latte->addExtension( $extension );
		}

		$this->latte->setTempDirectory( $this->cacheDirectory )
		            ->setLoader(
			            new Loader(
				            $this->templateDirectory,
				            self::$templates,
				            $this->latte->getExtensions(),
				            $this->preprocessors,
				            $this->logger,
			            ),
		            )
		;

		return $this->latte;
	}

	public function stopEngine() : self {
		if ( $this->latte ) {
			unset( $this->latte );
		}
		$this->stopwatch?->reset();
		$this->latte = null;
		return $this;
	}

	private function templateParameters( object | array $parameters ) : object | array {

		// $parameters = array_merge( $this->globalParameters(), $this->parameters, $parameters );

		// dd( $parameters );

		return $parameters;
	}

	/** Get the path to a template file, or a template string.
	 *
	 * @param  string  $load
	 * @return string
	 * @throws FileNotFoundException
	 */
	private function templateFilePath( string $load ) : string {

		// Assume it's a template string, if it contains '{' and '}'.
		if ( str_contains( $load, '{' ) && str_contains( $load, '}' ) ) {
			return $load;
		}

		// Ensure a templates directory exists.
		if ( !$this->templateDirectory ) {
			throw new FileNotFoundException(
				'No templates directory found.'
			);
		}

		$path = File::getPath( $this->templateDirectory . $load );

		// Ensure the template exists.
		if ( !$path ) {
			$available = glob( $this->templateDirectory . '*' );
			$available = implode( ",\n", str_replace( $this->templateDirectory, '', $available ) );
			throw new FileNotFoundException(
				"Template not found: $load\nIn directory:\n$available"
			);
		}

		return $path;
	}
}