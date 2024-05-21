<?php

declare( strict_types = 1 );

namespace Northrook\Symfony\Latte;

use Latte;
use Northrook\Support\Arr;
use Northrook\Support\Str;
use Northrook\Symfony\Latte\Compiler\RuntimeHookLoader;
use Northrook\Symfony\Latte\Extension\CoreExtension;
use Northrook\Symfony\Latte\Extension\RenderHookExtension;
use Northrook\Symfony\Latte\Variables\Application;
use Psr\Log\LoggerInterface;
use Stringable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Stopwatch\Stopwatch;

/*--------------------------------------------------------------------

    Latte Environment

- Handles Engine and Templates
- Loads Templates
- Injects variables
- Injects filters
- Injects extensions

/-------------------------------------------------------------------*/

// Provide a template for 404 and 500 pages.

class Environment
{

    /** The {@see Latte\Engine} instance used in this {@see Environment}. */
    private ?Latte\Engine $latte = null;

    /** @var Latte\Extension[] */
    private array $extensions = [];

    /**
     * `key=>value` store of all registered template directories
     *
     * @var array<string, string>
     */
    private readonly array $templateDirectories;

    //❔Tentative
    private array $templateStrings = [];

    public bool $autoRefresh;

    /**
     * @param string  $cacheDir        The directory to store compiled templates in.
     * @param string  $applicationKey  The key to use for the {@see Application} variable.
     * @param string  $documentKey     The key to use for the {@see Document} variable.
     */
    public function __construct(
        public readonly string                 $cacheDir,
        public readonly string                 $applicationKey,
        public readonly string                 $documentKey,
        private readonly Application           $applicationVariable,
        private readonly CoreExtension         $coreExtension,
        private readonly RenderHookExtension   $renderHookExtension,
        private readonly RuntimeHookLoader     $runtimeHook,
        private readonly ParameterBagInterface $parameterBag,
        private readonly ?LoggerInterface      $logger = null,
        private readonly ?Stopwatch            $stopwatch = null,
    ) {}


    public function addHook( string $name, string | Stringable $content ) : self {
        $this->runtimeHook->addHook( $name, $content );
        return $this;
    }

    /**
     * Render '$template' to string
     *
     * - Accepts a path to a template file or a template string.
     * - Parses `$parameters` into Latte variables.
     *
     * @param string             $template
     * @param object|array|null  $parameters
     *
     * @return string
     */
    public function render( string $template, object | array | null $parameters = null ) : string {

        $this->startEngine();

        $render = $this->latte->renderToString(
            $template,
            $this->templateParameters( $parameters ),
        );

        $this->stopwatch?->stop( 'Latte Engine' );

        return $render;
    }

    // ---------------------------------------------------------------------

    public function startEngine( ?Latte\Loader $loader = null ) : Environment {

        // Only start one Engine at a time.
        if ( $this->latte ) {
            return $this;
        }

        $this->stopwatch?->start( 'Latte Engine', 'Latte' );

        // Enable auto-refresh when debugging.
        // if ( !isset( $this->autoRefresh ) && $this->parameterBag->get( 'kernel.debug' ) ) {
        //     $this->logger->info( 'Auto-refresh enabled due to env:debug' );
        //     $this->autoRefresh = true;
        // }


        // Add included extensions.
        $this->addExtension( $this->coreExtension )
             ->addExtension( $this->renderHookExtension );

        // Initialize the Engine.
        $this->latte = new Latte\Engine();

        // Add all registered extensions to the Engine.
        foreach ( $this->extensions as $extension ) {
            $this->latte->addExtension( $extension );
        }

        // Use the included Loader unless one is provided.
        $loader ??= new Loader(
            directories : $this->getTemplateDirs(),
            templates   : $this->templateStrings,
            extensions  : $this->latte->getExtensions(),
            logger      : $this->logger,
            stopwatch   : $this->stopwatch,
        );

        $this->latte->setTempDirectory( $this->cacheDir )
                    ->setAutoRefresh( $this->autoRefresh ?? false )
                    ->setLoader( $loader );

        return $this;
    }

    // ---------------------------------------------------------------------


    /**
     * Add {@see Latte\Extension}s to this {@see Environment}.
     *
     * @param Latte\Extension  ...$extension
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
                $this->applicationKey => $this->applicationVariable,
            ] + $parameters,
        );
    }

    /**
     * Converts all array values to objects.
     *
     * @param array  $parameters
     *
     * @return array
     */
    public static function parameters( array $parameters ) : array {

        foreach ( $parameters as $key => $value ) {
            if ( is_array( $value ) ) {
                $parameters[ $key ] = (object) Environment::parameters( $value );
            }
        }

        return $parameters;
    }

    /**
     * Reset the Environment.
     *
     * - Stops the {@see Latte\Engine}
     * - Resets the {@see Stopwatch}
     *
     * @return $this
     */
    final public function stopEngine() : self {
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
     * ℹ️ The recompile proccess has stampede protection, so recompiling may take a while.
     *
     * @return bool Returns true on success
     */
    final public function clearCache() : bool {
        try {
            ( new Filesystem() )->remove( $this->cacheDir );
        }
        catch ( IOException $e ) {
            $this->logger->error( $e->getMessage() );
            return false;
        }

        return true;
    }

    /**
     * Retrieve all parameters registered as Latte directories
     *
     * @return array
     */
    final public function getTemplateDirs() : array {
        // TODO : May want  to cache this, check if the core.cache is sufficient.
        return $this->templateDirectories ??= Arr::unique(
            array_filter(
                array    : $this->parameterBag->all(),
                callback : static fn ( $value, $key ) => is_string( $value ) &&
                                                         Str::contains( $key, [ 'dir', 'templates' ] ),
                mode     : ARRAY_FILTER_USE_BOTH,
            ),
        );
    }

}