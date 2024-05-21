<?php declare( strict_types = 1 );

namespace Northrook\Symfony\Latte;

use JetBrains\PhpStorm\Deprecated;
use Latte;
use Latte\Extension;
use Northrook\Core\Interface\Printable;
use Northrook\Core\Type\PathType;
use Northrook\Support\Attributes\EntryPoint;
use Northrook\Support\Attributes\Output;
use Northrook\Support\File;
use Northrook\Support\Get;
use Northrook\Support\Str;
use Northrook\Support\Trim;
use Northrook\Symfony\Latte\Compiler\MissingTemplateException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Load templates from .latte files, preloaded templates, or raw string.
 */
final class Loader implements Latte\Loader
{
    private const TEMPLATE_DIR_PARAMETER = 'dir.latte.templates';

    /**
     * List of loaded templates during the current request.
     *
     * @var array<string, string>
     */
    private static array $loadedTemplates = [];

    /**
     * Holds available string templates.
     *
     * @var string|array<string, string>
     */
    private array $templates = [];

    /**
     * Holds the directories to search for templates.
     *
     * @var array<string, string>
     */
    private array $directories = [];

    private array $filterContent = [];

    private string $content;

    /**
     * Set by {@see getUniqueId()} when loading a template file.
     *
     * @var ?string
     */
    protected ?string $baseDir = null;

    /**
     * Set `true` by {@see getContent()} when loading a string template.
     *
     * @var bool
     */
    protected bool $isStringLoader = false;


    /**
     * @param null|string|array  $directories
     * @param null|string|array  $templates
     * @param Extension[]        $extensions
     * @param bool               $normalizeLatteTags
     * @param bool               $normalizeLatteBrackets
     * @param bool               $purgeComments
     * @param ?LoggerInterface   $logger
     * @param ?Stopwatch         $stopwatch
     */
    public function __construct(
        null | string | array             $directories = null,
        null | string | array             $templates = null,
        private readonly array            $extensions = [],
        protected bool                    $normalizeLatteTags = true,
        protected bool                    $normalizeLatteBrackets = true,
        protected bool                    $purgeComments = true,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?Stopwatch       $stopwatch = null,
    ) {
        $this->assignDirectories( $directories );
        $this->assignTemplates( $templates );
    }

    /**
     *
     * Pass an array of strings to be replaced.
     *
     * @param array<string, string|Printable>  $array
     *
     * @return void
     */
    public function filterContent( array $array ) : void {
        $this->filterContent = array_merge( $this->filterContent, $array );
    }

    private function handleLatteTags( string $content ) : string {

        // Tags not visible in Latte\Extension
        $tags = [ 'n:if' ];

        // Get all tags from all provided extensions
        foreach ( $this->extensions as $extension ) {

            /**
             * @var array<string, callable> $extensionTags
             */
            $extensionTags = $extension->getTags();

            $tags += array_keys( $extensionTags );
        }

        // Only keep n: tags
        $tags = array_filter( $tags, static fn ( $tag ) => str_starts_with( $tag, 'n:' ) );

        // Deduplicate tags
        $tags = array_flip( array_flip( $tags ) );

        // For debugging
        $this->logger->info(
            "Latte Loader: found {count} tags.",
            [ 'count' => count( $tags ) ],
        );

        // Match all found tags
        foreach ( $tags as $tag ) {
            $content = preg_replace_callback(
                pattern  : "#$tag=\"(.*?)\"#s",
                callback : static function ( array $match ) use ( $tag ) {
                    // Variables in n:tags must not be bracketed, trim that and any excess whitespace
                    $value = Trim::whitespace( trim( $match[ 1 ], " {}" ) );
                    return $value ? "$tag=\"$value\"" : null;
                },
                subject  : $content,
            );
        }

        return $content;
    }

    private function prepareTemplate( string $content ) : string {

        // Ensure elements are not broken across multiple lines.
        $content = preg_replace_callback(
            pattern  : '#<\s*[a-zA-Z][:a-zA-Z0-9]*\s+[^>]*>#',
            callback : static fn ( array $match ) => Trim::whitespace(
                $match[ 0 ], true, true,
            ),
            subject  : $content,
        );

        // Safely handle object operators
        $content = preg_replace(
            '#->(?=\w)#', // Match all object operators
            '%%OBJECT_OPERATOR%%',
            $content,
        );

        // Ensure proper handling of Latte tags and their variables
        if ( $this->normalizeLatteTags ) {
            $content = $this->handleLatteTags( $content );
        }

        // Remove all comments from the template, they serve not purpose in the compiled template
        if ( $this->purgeComments ) {
            $content = Trim::whitespace( $content );
        }

        return $content;
    }

    /**
     * - Prepare and compile the template.
     * - The content can be filtered using {@see filterContent()}.
     *
     * @param string  $content
     *
     * @return string
     */
    private function compile( string $content ) : string {

        $this->content = $this->prepareTemplate( $content );

        foreach ( $this->filterContent as $find => $replace ) {

            // Determine if the $replace value is a component
            $isComponent = $replace instanceof Printable;

            // If it is a component, print it and start a stopwatch
            if ( $isComponent ) {
                $component = 'Component: ' . Get::className( $replace );
                $this->stopwatch->start( $component, 'Latte' );
                $replace = $replace->print();
            }

            // Compress the $replace value and replace it in the content
            $replace       = Trim::whitespace( $replace );
            $this->content = str_ireplace( $find, $replace, $this->content );

            // If it is a component, stop the respective stopwatch
            if ( $isComponent ) {

                // TODO : Consider warning if the is unexpected slow
                // $slow = match ( true ) {
                //     $time >= 55 => 'error',
                //     $time >= 35 => 'warning',
                //     $time >= 25 => 'notice',
                //     default     => false,
                // };

                $this->stopwatch->stop( $component );
            }
        }

        // Set object operators
        $this->content = str_ireplace(
            search  : '%%OBJECT_OPERATOR%%',
            replace : '->',
            subject : $this->content,
        );

        return $this->content;
    }


    /**
     * Returns template source code to the {@see Engine}.
     *
     * @param string  $name
     *
     * @return string
     *
     * @throws Latte\RuntimeException if the template cannot be found.
     */
    #[Output( [ Latte\Engine::class, 'compile' ] )]
    public function getContent( string $name ) : string {

        $name = str_replace( [ '/', '\\' ], DIRECTORY_SEPARATOR, $name );

        if ( $this->isStringLoader ) {
            $content                                    = $this->getTemplateString( $name );
            Loader::$loadedTemplates[ $name ][ 'size' ] = strlen( $content );
        }
        else {
            $path                                       = $this->getTemplatePath( $name );
            $content                                    = File::getContents( $path );
            Loader::$loadedTemplates[ $name ][ 'size' ] = filesize( $path ) . ' bytes';
        }

        $content = $this->compile( $content );


        return $content;
    }

    /**
     * Return a registered string template.
     *
     * @param string  $name
     *
     * @return string
     *
     * @throws Latte\RuntimeException if the template does not exist.
     */
    private function getTemplateString( string $name ) : string {

        // Try to find the requested template by key
        $template = $this->templates[ $name ] ?? null;

        // If it does not exist, and there is only one template, use it
        if ( null === $template && count( $this->templates ) === 1 ) {
            $template = $this->templates[ 0 ] ?? null;
        }

        // If it does not exist, and there are no templates, throw an exception
        if ( !$template ) {
            throw new Latte\RuntimeException(
                "Missing string template: '$name'.\nPlease check the templates added to the Loader.",
            );
        }

        return $template;
    }

    /**
     * Match the template name against the registered directories.
     *
     * @param string  $name  Template name
     *
     * @return string Path to the template file
     *
     * @throws Latte\RuntimeException if the template does not exist.
     */
    private function getTemplatePath( string $name ) : string {

        // Check if the Application has the requested template
        $path = ( $this->directories[ Loader::TEMPLATE_DIR_PARAMETER ] ?? null ) . $name;

        // If it does, return the path
        if ( $path && file_exists( $path ) ) {
            return $this->useTemplate( $name, Loader::TEMPLATE_DIR_PARAMETER, $path, false );
        }

        // Else, loop through registered template directories until one is found
        foreach ( $this->directories as $parameter => $path ) {
            if ( file_exists( $path . $name ) ) {
                return $this->useTemplate( $name, $path, $path . $name, false );
            }
        }

        // If it does not exist, and there are no templates, throw an exception
        throw new Latte\RuntimeException( "Missing template file: '$name'." );
    }

    /**
     * Returns unique identifier for caching.
     *
     * - This is the first method called by {@see Engine::getTemplateClass()}.
     * - The {@see Engine::generateCacheHash()} method hashes the template content into a unique identifier.
     *
     */
    #[EntryPoint]
    public function getUniqueId( string $name ) : string {

        $this->isStringLoader = !str_ends_with( $name, '.latte' );

        if ( $this->isStringLoader ) {
            return Str::key( $this->getContent( $name ) );
        }

        return PathType::normalize( $this->baseDir = DIRECTORY_SEPARATOR, $name );
    }

    /**
     * # ☑️
     *
     * Returns referred template name.
     *
     * - Will throw a {@see \LogicException} if the template does not exist.
     *
     * @param string  $name
     * @param string  $referringName
     *
     * @return string
     */
    public function getReferredName( string $name, string $referringName ) : string {

        $this->logger->debug(
            "Latte Loader: getReferredName( {name}, {referringName} )",
            [ 'name' => $name, 'referringName' => $referringName ],
        );

        if ( $this->isStringLoader && false === $this->hasStringTemplate() ) {
            throw new MissingTemplateException(
                "Latte tried parsing the string template '$name'.\nPlease check the templates added to the Loader.",
                $this->templates,
                [
                    'name'          => $name,
                    'referringName' => $referringName,
                ],
            );
        }

        if ( $this->baseDir || !preg_match( '#/|\\\\|[a-z][a-z0-9+.-]*:#iA', $name ) ) {
            $name = PathType::normalize( $referringName . '/../' . $name );
            $this->logger->debug(
                "Latte Loader: loading template file '$name'.",
                [ 'name' => $name, 'referringName' => $referringName ],
            );
        }

        return $name;
    }


    /**
     * Checks whether the Loader has a string template.
     *
     * - Checks for any template by default.
     * - Pass a string to check for specific template.
     *
     * @param null|string  $template
     *
     * @return bool
     */
    public function hasStringTemplate( ?string $template = null ) : bool {
        return $template ? isset( $this->templates[ $template ] ) : !empty( $this->templates );
    }

    /**
     * @param string  $name
     * @param int     $time
     *
     * @return bool
     *
     * @deprecated Since Latte version 3.0.16
     * @link       https://github.com/nette/latte/releases/tag/v3.0.16 Deprecated since Latte version 3.0.16
     */
    public function isExpired( string $name, int $time ) : bool {
        return false;
    }

    // Support Methods --------------------------------------------------------------

    private function assignDirectories( null | string | array $directories ) : void {

        // Bail if no templates are provided
        if ( !$directories || ( is_array( $directories ) && empty( $directories ) ) ) {
            return;
        }

        if ( is_string( $directories ) ) {
            $this->directories[ Loader::TEMPLATE_DIR_PARAMETER ] = $directories;
        }
        else {
            $this->directories = $this->filterTemplateArray( $directories );
        }
    }

    private function assignTemplates( null | string | array $templates ) : void {

        // Bail if no templates are provided
        if ( !$templates || ( is_array( $templates ) && empty( $templates ) ) ) {
            return;
        }

        if ( is_string( $templates ) ) {
            $this->templates = [ $templates ];
        }
        else {
            $this->templates = $this->filterTemplateArray( $templates );
        }
    }

    /**
     * Filters an array of templates.
     *
     * - Only assign templates with string keys
     * - Empty or non-stringable templates will be ignored
     *
     * @param array  $templates
     *
     * @return array
     */
    private function filterTemplateArray( array $templates ) : array {
        return array_filter(
            array    : $templates,
            callback : static fn ( $template, $key ) => (
                $template &&
                is_string( $template ) &&
                is_string( $key )
            ),
            mode     : ARRAY_FILTER_USE_BOTH,
        );
    }


    /**
     * Returns an array of templates used by the Loader.
     *
     * @return string[]
     */
    public static function getLoadedTemplates() : array {
        return Loader::$loadedTemplates;
    }


    // TODO : Add support for string templates
    private function useTemplate(
        string $name,
        string $bundle,
        string $path,
        bool   $isStringLoader,
    ) : string {

        $source  = array_filter( explode( DIRECTORY_SEPARATOR, $bundle ) );
        $lastKey = array_key_last( $source );

        if ( $lastKey > 2 && $source[ $lastKey ] === 'templates' ) {
            $bundle = $source[ $lastKey - 1 ];
        }

        $path = PathType::normalize( $path );

        Loader::$loadedTemplates[ $name ] = [
            'name'        => $name,
            'bundle'      => $bundle,
            'path'        => $path,
            "size"        => null,
            'type'        => $isStringLoader ? "string" : "file",
            'loadedCount' => 0,
        ];

        Loader::$loadedTemplates[ $name ][ 'loadedCount' ]++;

        return $path;
    }
}