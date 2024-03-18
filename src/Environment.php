<?php

/** @noinspection PhpUnused */

declare( strict_types = 1 );


namespace Northrook\Symfony\Latte;

use Latte;
use Latte\Engine;
use Latte\Extension;
use Northrook\Symfony\Latte\Parameters\DocumentParameters;
use Northrook\Symfony\Latte\Parameters\GlobalParameters;
use Northrook\Types\Path;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * * AbstractCoreController
 *
 */
class Environment
{

    private static array $templates = [];

    private ?Latte\Engine $latte      = null;
    private array         $parameters = [];

    /** @var Extension[] */
    private array $extensions = [];

    /** @var Preprocessor[] */
    private array $preprocessors = [];

    public readonly Options $options;


    public function __construct(
        public string                     $templateDirectory,
        public string                     $cacheDirectory,
        private readonly CoreExtension    $coreExtension,
        private readonly GlobalParameters $globalParameters,
        protected ?LoggerInterface        $logger = null,
        protected ?Stopwatch              $stopwatch = null,
        private ?DocumentParameters       $documentParameters = null,
    ) {
        $this->options = new Options();
    }


    /** Render '$template' to string
     *
     * * Accepts a path to a template file or a template string.
     * * Parses `$parameters` into Latte variables.
     *
     * @param string             $template
     * @param object|array|null  $parameters
     *
     * @return string
     */
    public function render( string $template, object | array | null $parameters = null ) : string {

        $this->latte ??= $this->startEngine();

        $render = $this->latte->renderToString(
            $this->templateFilePath( $template ),
            $this->templateParameters( $parameters ),
        );

        $this->stopwatch?->stop( 'engine' );

        return $render;
    }

    public function clearCache() : void {

        $fs = new Filesystem();

        $fs->remove( $this->cacheDirectory );

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

    public function setDocumentParameters( DocumentParameters $documentParameters ) : void {
        $this->documentParameters = $documentParameters;
    }

    public function addPreprocessor( Preprocessor ...$preprocessor ) : self {

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
     * @param Engine|null  $engine   The engine to use, defaults to Latte
     * @param mixed        ...$args  Arguments to pass to the custom engine
     *
     * @return Engine
     */
    public function startEngine( ?Latte\Engine $engine = null, mixed ...$args ) : Latte\Engine {

        if ( $this->latte ) {
            return $this->latte;
        }

        $this->stopwatch?->start( 'engine', 'latte' );

        $this->latte = new ( $engine ?? Latte\Engine::class )( ...$args );

        $this->addExtension( $this->coreExtension );

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

    /**
     * @param object|array|null  $parameters
     *
     * @return object|array
     */
    private function templateParameters( object | array | null $parameters ) : object | array {

        if ( is_object( $parameters ) ) {
            return $parameters;
        }

        $this->parameters[ $this->options->globalVariable ] = $this->globalParameters;

        if ( null === $parameters && $this->documentParameters ) {
            $this->parameters[ $this->options->documentVariable ] = $this->documentParameters;

            return $this->documentParameters;
        }

        if ( array_key_exists( $this->options->globalVariable, $parameters ) ) {
            $this->logger->error(
                'The {key} parameter is reserved and cannot be used as a template parameter. It has been {action}.',
                [ 'key' => $this->options->globalVariable, 'action' => 'unset' ],
            );
            unset( $parameters[ $this->options->globalVariable ] );
        }

        if ( $this->documentParameters && array_key_exists( $this->options->documentVariable, $parameters ) ) {
            $this->logger->error(
                'The {key} parameter is reserved and cannot be used as a template parameter. It has been {action}.',
                [ 'key' => $this->options->documentVariable, 'action' => 'unset' ],
            );
            unset( $parameters[ $this->options->documentVariable ] );
        }

        $parameters = array_merge( $this->parameters, (array) $parameters );

        return static::parameters( $parameters );
    }

    /** Get the path to a template file, or a template string.
     *
     * @param string  $load
     *
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

        $path = new Path( $this->templateDirectory . $load );

        // Ensure the template exists.
        if ( !$path->isValid ) {
            $available = glob( $this->templateDirectory . '*' );
            $available = implode( ",\n", str_replace( $this->templateDirectory, '', $available ) );
            throw new FileNotFoundException(
                "Template not found: $load\nIn directory:\n$available"
            );
        }

        return $path->value;
    }
}