<?php declare( strict_types = 1 );

namespace Northrook\Symfony\Latte;

use Latte;
use Latte\CompileException;
use LogicException;
use Northrook\Support\Attribute\EntryPoint;
use Northrook\Support\File;
use Northrook\Support\Str;
use Psr\Log\LoggerInterface;

/** Load templates from .latte files, preloaded templates, or raw string.
 *
 * * Reads the template data, passing it through each precompiler if supplied.
 *
 * @api Entry point
 */
class Loader implements Latte\Loader
{

	private const NORMALIZE_VARIABLES = [
		'{ $'       => '{$',
		'$}'        => '$}',
		'{ ('       => '{(',
		') }'       => ')}',
		'%%ARROW%%' => '->',
	];

	private bool $isStringLoader = false;

	private ?LoggerInterface $logger = null;

	public function __construct(
		// The base directory for templates, root/templates by default.
		public readonly ?string $baseDir,
		// Key-value array of name and template markup.
		public readonly ?array  $templates = null,
		private readonly array  $extensions = [],
		private readonly array  $compilers = [],
		?LoggerInterface        $logger = null,
	) {
		$this->logger = $logger;
	}

	/**
	 * * TODO: [mid] Improve the regex pattern for matching {$variable_Names->values}
	 *                  Case-sensitivity, special characters like underscore etc.
	 *
	 * @param  string  $content
	 * @return string
	 */
	public static function prepare( string $content ) : string {

		$content = preg_replace_callback(
			"/\\\$[a-zA-Z?>._':$\s\-]*/ms",
			function ( array $m ) {
				return str_replace( '->', '%%ARROW%%', $m[ 0 ] );
			},
			$content,
		);

		return $content;
	}

	/**
	 * * Prepare and compile the template.
	 * * The content is passed through each precompiler.
	 *
	 * @throws CompileException
	 */
	private function compile( string $content ) : string {

		$content = $this->latteTags( $content );
		$content = $this::prepare( $content );

		foreach ( $this->compilers as $compiler ) {

			if ( class_exists( $compiler ) ) {
				$step = new $compiler( $content );
				if ( method_exists( $step, '__toString' ) ) {
					$content = (string) $step;
				}
				else {
					throw new Latte\CompileException(
						"Class $compiler\n must implement __toString()"
					);
				}
			}
			else {
				if ( is_callable( $compiler ) ) {
					$content = (string) $compiler( $content );
				}
				else {
					throw new Latte\CompileException(
						"Compiler $compiler\n is not a class or callable"
					);
				}
			}
		}


		$content = str_ireplace(
			array_keys( self::NORMALIZE_VARIABLES ),
			array_values( self::NORMALIZE_VARIABLES ),
			$content,
		);

		// dd( $content );
		return $content;
	}


	/** Ensures that Latte tags are parsed correctly.
	 *
	 * * Finds all registered n:tags.
	 * * Parses {$tag->var} into $tag->var within n:tags
	 *
	 * @param  string  $content
	 * @return string
	 */
	private function latteTags( string $content ) : string {

		// Tags not visible in Latte\Extension
		$tags = [ 'n:if' ];

		foreach ( $this->extensions as $extension ) {
			if ( $extension instanceof Latte\Extension ) {
				$extension = $extension->getTags();
			}

			if ( is_string( $extension ) ) {
				$tags[] = $extension;
			}

			else if ( !is_array( $extension ) ) {
				continue;
			}

			foreach ( $extension as $tag => $parser ) {
				if ( str_starts_with( $tag, 'n:' ) ) {
					$tags[] = $tag;
				}
			}
		}


		foreach ( $tags as $tag ) {


			$content = preg_replace_callback(
				"/$tag=(?:\")(.*?)(?:\")/ms",
				function ( array $m ) use ( $tag ) {
					$var = trim( $m[ 1 ], ' {}' );
					if ( str_contains( $var, '$' ) && !str_contains( $var, '??' ) ) {
						$var .= '??false';
					}
					// dump( $var );
					return "{$tag}=\"{$var}\"";
				},
				$content,
			);

		}

		// dd( $content );
		return $content;
	}

	public function getContent( string $name ) : string {

		if ( $this->isStringLoader ) {

			if ( is_array( $this->templates ) ) {

				$content = $this->templates[ $name ] ?? '';

				if ( !$content ) {
					if ( $this->logger ) {
						$this->logger->error( 'Missing requested template {name}.', [
							'name'      => $name,
							'templates' => $this->templates,
							'backtrace' => debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3 ),
						] );
					}
					else {
						throw new Latte\RuntimeException( "Missing template '$name'." );
					}
				}

			}
			else {
				$content = $name;
			}

		}
		else {

			$file = Str::filepath( $name, $this->baseDir);

			if ( $this->baseDir && !str_starts_with( $this->normalizePath( $file ), $this->baseDir ) ) {
				throw new Latte\RuntimeException(
					"Template '$file' is not within the allowed path '$this->baseDir'."
				);

			}
			else {
				if ( !is_file( $file ) ) {
					throw new Latte\RuntimeException( "Missing template file '$file'." );

				}
				else {
					if ( $this->isExpired( $name, time() ) ) {
						if ( @touch( $file ) === false ) {
							trigger_error(
								"File's modification time is in the future. Cannot update it: " . error_get_last(
								                                                                  )[ 'message' ],
								E_USER_WARNING,
							);
						}
					}
				}
			}

			$content = file_get_contents( $file );
		}

		return $this->compile( $content );
	}

	/** Checks whether template has expired.
	 *
	 * * Expired templates will be regenerated on demand.
	 *
	 * @param  string  $file
	 * @param  int  $time
	 * @return bool
	 */
	public function isExpired( string $file, int $time ) : bool {
		if ( $this->isStringLoader ) {
			return false;
		}
		$mtime = @filemtime( $this->baseDir . $file ); // @ - stat may fail

		return !$mtime || $mtime > $time;
	}

	/** Returns referred template name.
	 *
	 * @param  string  $name
	 * @param  string  $referringName
	 * @return string
	 */
	public function getReferredName( string $name, string $referringName ) : string {

		if ( $this->isStringLoader ) {
			if ( $this->templates === null ) {
				throw new LogicException(
					"Missing template '$name'."
				);
			}

			return $name;
		}

		if ( $this->baseDir || !preg_match( '#/|\\\\|[a-z][a-z0-9+.-]*:#iA', $name ) ) {
			$name = $this->normalizePath( $referringName . '/../' . $name );
		}

		return $name;
	}

	/**
	 * Returns unique identifier for caching.
	 *
	 * * This is the first method called by {@see Engine::getTemplateClass()}.
	 *
	 * @version 1.0.0 ✅
	 */
	#[EntryPoint]
	public function getUniqueId( string $name ) : string {

		$this->isStringLoader = !str_ends_with( $name, '.latte' );

		if ( $this->isStringLoader ) {
			return $this->getContent( $name );
		}

		return $this->baseDir . strtr( $name, '/', DIRECTORY_SEPARATOR );

	}

	/** File Loader Only
	 *
	 * @param  string  $string
	 * @return string
	 */
	private function normalizePath( string $string ) : string {

		$path = [];
		$string = strtr( $string, '\\', '/' );

		if ( str_contains( $string, '/' ) === false ) {
			return $string;
		}

		foreach ( explode( '/', $string ) as $part ) {
			if ( $part === '..' && $path && end( $path ) !== '..' ) {
				array_pop( $path );
			}
			else {
				if ( $part !== '.' ) {
					$path[] = $part;
				}
			}
		}

		return implode(
			separator : DIRECTORY_SEPARATOR,
			array     : $path,
		);
	}
}