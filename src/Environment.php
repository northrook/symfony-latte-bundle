<?php

/** @noinspection PhpUnused */

declare( strict_types = 1 );


namespace Northrook\Symfony\Latte;

use JetBrains\PhpStorm\Deprecated;
use Latte;
use Latte\Engine;
use Latte\Extension;
use Northrook\Types\Path;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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
    // private array         $templateDirectories = [];
    private ?Latte\Engine $latte      = null;
    private array         $parameters = [];

    /** @var Extension[] */
    private array $extensions = [];

    /** @var Preprocessor[] */
    private array $preprocessors = [];


    public Path $cacheDirectory;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly CoreExtension         $coreExtension,
        private readonly Parameters            $globalParameters,
        protected ?LoggerInterface             $logger = null,
        protected ?Stopwatch                   $stopwatch = null,
    ) {
        $this->cacheDirectory = new Path( $this->parameterBag->get( 'dir.latte.cache' ) );
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
            $template,
//            $this->templateFilePath( $template ),
            $this->templateParameters( $parameters ),
        );

        $this->stopwatch?->stop( 'engine' );

        return $render;
    }

    #[Deprecated]
    public function clearCache() : void {

        $fs = new Filesystem();

        $fs->remove( (string) $this->cacheDirectory );

    }

    public static function addTemplate( array $array ) : void {
        foreach ( $array as $name => $template ) {
            self::$templates[ strtolower( $name ) ] = $template;
        }
    }

    #[Deprecated]
    public static function parameters( array $parameters ) : array {

        foreach ( $parameters as $key => $value ) {
            if ( is_array( $value ) ) {
                $parameters[ $key ] = (object) self::parameters( $value );
            }
        }

        return $parameters;
    }

    #[Deprecated]
    public function addPreprocessor( Preprocessor ...$preprocessor ) : self {

        $this->preprocessors = array_merge( $this->preprocessors, $preprocessor );

        return $this;
    }

    #[Deprecated]
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

        $this->latte->setTempDirectory( (string) $this->cacheDirectory )
                    ->setLoader(
                        new Loader(
                            $this->parameterBag,
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
    #[Deprecated]
    private function templateParameters( object | array | null $parameters ) : object | array {

        if ( is_object( $parameters ) ) {
            return $parameters;
        }

        $this->parameters[ $this->parameterBag->get( 'latte.global_parameters_key' ) ] = $this->globalParameters;

        //
        // if ( null === $parameters && $this->documentParameters ) {
        //     $this->parameters[ $this->options->documentVariable ] = $this->documentParameters;
        //
        //     return $this->documentParameters;
        // }
        //
        // if ( array_key_exists( $this->options->globalVariable, $parameters ) ) {
        //     $this->logger->error(
        //         'The {key} parameter is reserved and cannot be used as a template parameter. It has been {action}.',
        //         [ 'key' => $this->options->globalVariable, 'action' => 'unset' ],
        //     );
        //     unset( $parameters[ $this->options->globalVariable ] );
        // }
        //
        // if ( $this->documentParameters && array_key_exists( $this->options->documentVariable, $parameters ) ) {
        //     $this->logger->error(
        //         'The {key} parameter is reserved and cannot be used as a template parameter. It has been {action}.',
        //         [ 'key' => $this->options->documentVariable, 'action' => 'unset' ],
        //     );
        //     unset( $parameters[ $this->options->documentVariable ] );
        // }

        $parameters = array_merge( $this->parameters, (array) $parameters );

        return static::parameters( $parameters );
    }

    /** Get the path to a template file, or a template string.
     *
     * @param string  $load
     *
     * @return string
     * @throws FileNotFoundException
     *
     */
    #[Deprecated]
    private function templateFilePath( string $load ) : string {

//        // Assume it's a template string, if it contains '{' and '}'.
//        if ( str_contains( $load, '{' ) && str_contains( $load, '}' ) ) {
//            return $load;
//        }
//
//        // Ensure a templates directory exists.
//        if ( !$this->templateDirectory ) {
//            throw new FileNotFoundException(
//                'No templates directory found.'
//            );
//        }
//
//        $path = new Path( $this->templateDirectory . $load );
//
//        // Ensure the template exists.
//        if ( !$path->isValid ) {
//            $available = glob( $this->templateDirectory . '*' );
//            $available = implode( ",\n", str_replace( $this->templateDirectory, '', $available ) );
//            throw new FileNotFoundException(
//                "Template not found: $load\nIn directory:\n$available"
//            );
//        }
//
//        return $path->value;

        return $load;
    }
}