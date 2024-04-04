<?php
declare( strict_types = 1 );

namespace Northrook\Symfony\Latte\Core;

use Latte;
use Northrook\Symfony\Latte\Loader;
use Northrook\Symfony\Latte\Parameters as Parameters;
use Northrook\Symfony\Latte\Preprocessor\Preprocessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Stopwatch\Stopwatch;

class Environment
{
    private ?Latte\Engine $latte = null;

    private readonly string $cacheDirectory;

    /** @var Latte\Extension[] */
    private array $extensions = [];

    /** @var Preprocessor[] */
    private array $preprocessors = [];

    private readonly ParameterBagInterface  $parameterBag;
    private readonly Parameters\Application $application;
    private readonly ?LoggerInterface       $logger;
    private readonly ?Stopwatch             $stopwatch;


    final public function dependencyInjection(
        ParameterBagInterface  $parameterBag,
        Parameters\Application $application,
        ?LoggerInterface       $logger = null,
        ?Stopwatch             $stopwatch = null,
    ) : void {
        $this->parameterBag   = $parameterBag;
        $this->application    = $application;
        $this->logger         = $logger;
        $this->stopwatch      = $stopwatch;
        $this->cacheDirectory = $this->parameterBag->get( 'dir.latte.cache' );
    }

    /**
     * Add {@see Latte\Extension}s to this {@see Environment}.
     *
     * @param Extension  ...$extension
     *
     * @return $this
     */
    public function addExtension( Latte\Extension ...$extension ) : self {

        foreach ( $extension as $ext ) {
            if ( in_array( $ext, $this->extensions, true ) ) {
                continue;
            }
            $this->extensions[] = $ext;
        }

        return $this;
    }

    /**
     * Add {@see Preprocessor}s to this {@see Environment}.
     *
     * @param Preprocessor  ...$preprocessor
     *
     * @return $this
     *
     * @todo [low] Refactor {@see Preprocessor} system.
     */
    public function addPreprocessor( Preprocessor ...$preprocessor ) : self {

        foreach ( $preprocessor as $ext ) {
            if ( in_array( $ext, $this->preprocessors, true ) ) {
                continue;
            }
            $this->preprocessors[] = $ext;
        }

        return $this;
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
            $this->templateParameters( $parameters ),
        );

        $this->stopwatch?->stop( 'engine' );

        return $render;
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
     * @param Latte\Engine|null  $engine   The engine to use, defaults to Latte
     * @param mixed              ...$args  Arguments to pass to the custom engine
     *
     * @return Latte\Engine
     */
    public function startEngine( ?Latte\Engine $engine = null, mixed ...$args ) : Latte\Engine {

        if ( $this->latte ) {
            return $this->latte;
        }

        $this->stopwatch?->start( 'engine', 'latte' );

        $this->latte = new ( $engine::class ?? Latte\Engine::class )( ...$args );

        foreach ( $this->extensions as $extension ) {
            $this->latte->addExtension( $extension );
        }

        $this->latte->setTempDirectory( $this->cacheDirectory )
                    ->setLoader(
                        new Loader(
                            $this->parameterBag,
                            [], // self::$templates
                            $this->latte->getExtensions(),
                            $this->preprocessors,
                            $this->logger,
                            $this->stopwatch,
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
     * Clear the Latte Application Cache.
     *
     * ⚠️ This will cause all templates to be recompiled on-demand.
     *
     * @return bool Returns true on success
     */
    public function clearCache() : bool {
        try {
            ( new Filesystem() )->remove( $this->cacheDirectory );
        }
        catch ( IOException $e ) {
            $this->logger?->error( $e->getMessage() );
            return false;
        }

        return true;
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

        return Environment::parameters(
            [
                $this->parameterBag->get( 'latte.parameter_key.application' ) => $this->application,
            ] + $parameters,
        );
    }

    public static function parameters( array $parameters ) : array {

        foreach ( $parameters as $key => $value ) {
            if ( is_array( $value ) ) {
                $parameters[ $key ] = (object) Environment::parameters( $value );
            }
        }

        return $parameters;
    }

}