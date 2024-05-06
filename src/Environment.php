<?php

declare( strict_types = 1 );

namespace Northrook\Symfony\Latte;

/*--------------------------------------------------------------------

    Latte Environment

- Handles Engine and Templates
- Loads Templates
- Injects variables
- Injects filters
- Injects extensions

/-------------------------------------------------------------------*/

use Closure;
use Latte;
use Northrook\Core\Service\ServiceResolver;
use Northrook\Core\Service\ServiceResolverTrait;
use Northrook\Logger\Log;
use Northrook\Support\Arr;
use Northrook\Support\Str;
use Northrook\Symfony\Latte\Compiler\RuntimeHookLoader;
use Northrook\Symfony\Latte\Extension\CoreExtension;
use Northrook\Symfony\Latte\Extension\RenderHookExtension;
use Northrook\Symfony\Latte\Preprocessor\Preprocessor;
use Northrook\Symfony\Latte\Variables\Application;
use Psr\Log\LoggerInterface;
use Stringable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @property Application           $applicationVariable
 * @property CoreExtension         $coreExtension
 * @property RenderHookExtension   $renderHookExtension
 * @property ParameterBagInterface $parameterBag
 * @property LoggerInterface       $logger
 * @property Stopwatch             $stopwatch
 * @property  RuntimeHookLoader    $hookLoader
 */
class Environment extends ServiceResolver
{
    use  ServiceResolverTrait;

    private ?Latte\Engine  $latte = null;
    private readonly array $templateDirs;

    /** @var Latte\Extension[] */
    private array $extensions = [];

    /** @var Preprocessor[] */
    private array $preprocessors = [];

    public bool $autoRefresh = true;

    public function __construct(
        public readonly string          $cacheDir,
        public readonly string          $applicationKey,
        Application | Closure           $applicationVariable,
        CoreExtension | Closure         $coreExtension,
        RenderHookExtension | Closure   $renderHookExtension,
        RuntimeHookLoader | Closure     $hookLoader,
        ParameterBagInterface | Closure $parameterBag,
        LoggerInterface | Closure       $logger,
        Stopwatch | Closure             $stopwatch,
    ) {
        $this->setMappedService( get_defined_vars() );
    }

    public function addHook( string $name, string | Stringable $content ) : self {
        $this->hookLoader->addHook( $name, $content );
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

    public function startEngine() : Latte\Engine {

        if ( $this->latte ) {
            return $this->latte;
        }

        $this->stopwatch?->start( 'engine', 'latte' );

        $this->addExtension( $this->coreExtension )
             ->addExtension( $this->renderHookExtension );

        $this->latte = new Latte\Engine();

        foreach ( $this->extensions as $extension ) {
            $this->latte->addExtension( $extension );
        }

        $loader = new Loader(
            $this->getTemplateDirs(),
            $this->latte->getExtensions(),
            $this->preprocessors,
            $this->logger,
            $this->stopwatch,
        );

        $this->latte->setTempDirectory( $this->cacheDir )
                    ->setAutoRefresh( $this->autoRefresh )
                    ->setLoader( $loader );


        return $this->latte;
    }

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
     * @return bool Returns true on success
     */
    final public function clearCache() : bool {
        try {
            ( new Filesystem() )->remove( $this->cacheDir );
        }
        catch ( IOException $e ) {
            Log::Error( $e->getMessage() );
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
        return $this->templateDirs ??= Arr::unique(
            array_filter(
                array    : $this->parameterBag->all(),
                callback : static fn ( $value, $key ) => is_string( $value ) &&
                                                         Str::contains( $key, [ 'dir', 'templates' ] ),
                mode     : ARRAY_FILTER_USE_BOTH,
            ),
        );
    }
}