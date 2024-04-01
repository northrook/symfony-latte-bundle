<?php

namespace Northrook\Symfony\Latte\Parameters;

use JetBrains\PhpStorm\ExpectedValues;
use Northrook\Elements\Element\Attributes;
use Northrook\Favicon\FaviconBundle;
use Northrook\Support\Str;
use Northrook\Symfony\Assets\Script;
use Northrook\Symfony\Assets\Stylesheet;
use Northrook\Symfony\Core\File;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;


/**
 * @property string  $robots
 * @property string  $title
 * @property string  $description
 * @property array   $scripts
 * @property array   $stylesheets
 * @property array   $meta
 * @property ?string $tileColor     = null;
 * @property ?string $browserconfig = '/browserconfig.xml';
 */
class Document
{

    private readonly string $publicDir;
    private array           $printed = [];

    /** @var Script[] */
    protected array $script = [];

    /** @var Stylesheet[] */
    protected array $stylesheet = [];

    protected array $meta = [];


    public readonly Attributes $body;
    public bool                $manifest      = true;
    public bool                $msapplication = true;

    public function __construct(
        private readonly Application      $application,
        private readonly Content          $content,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?Stopwatch       $stopwatch = null,
    ) {

        $this->publicDir = File::path( 'dir.public' );

        $this->body = new Attributes(
            id    : $this->documentPath( 'key' ),
            class : 'test cass',
        );

        $this->meta = [
            // 'robots'  => $this->getRobots(),
            // 'title'   => $this->getTitle(),
            // 'content' => [
            //     'description' => $this->getDescription(),
            //     'keywords'    => $this->getKeywords(),
            //     'author'      => $this->getAuthor(),
            // ],
            // // 'canonical'   => $this->getCanonical(),
        ];

    }


    public function unset( string $meta ) : self {
        unset( $this->meta[ $meta ] );

        return $this;
    }

    public function __get( string $name ) {

        if ( isset( $this->$name ) ) {
            $this->printed[] = $name;
            return $this->$name;
        }

        $get = "get" . ucfirst( $name );
        if ( method_exists( $this, $get ) ) {
            $this->printed[] = $name;
            return $this->$get();
        }

        return null;
    }

    public function __set( string $name, mixed $value ) {
        $this->meta[ $name ] = $value;
    }

    public function __isset( string $name ) {
        return isset( $this->meta[ $name ] );
    }


    public function meta( ...$get ) : array {
        $meta = [];
        if ( count( $get ) === 1 ) {
            $get = match ( $get[ 0 ] ) {
                'all'           => array_keys( $this->meta ),
                'info'          => [ 'title', 'description', 'keywords', 'robots', 'author' ],
                'msapplication' => [ 'tileColor', 'browserconfig' ],
                default         => $get,
            };
        }

        foreach ( $get as $name ) {

            if ( in_array( $name, $this->printed, true ) ) {
                continue;
            }

            $this->printed[] = $name;
            $value           = null;

            if ( isset( $this->meta[ $name ] ) ) {
                $value = $this->meta[ $name ];
            }
            elseif ( property_exists( $this, $name ) ) {
                $value = $this->$name;
            }
            else {
                $method = "get" . ucfirst( $name );
                if ( method_exists( $this, $method ) ) {
                    $value = $this->$method();
                }
            }

            if ( $value === null ) {
                continue;
            }

            $meta[ $name ] = [
                'name'    => $name,
                'content' => $value,
            ];
        }

        return $meta;
    }

    private function getMeta() : array {
        return $this->meta;
    }

    private function getScripts() : array {
        return $this->script;
    }

    private function getStylesheets() : array {
        return $this->stylesheet;
    }

    private function getTitle() : ?string {

        $title = $this->meta[ 'title' ] ?? null;

        // Fallback to path
        $title ??= $this->documentPath( 'title' );

        return $title;
    }

    private function getDescription() : ?string {
        return null;
    }

    private function getKeywords() : ?string {
        return null;
    }

    private function getRobots() : ?string {
        return null;
    }

    private function getAuthor() : ?string {
        return null;
    }

    private function getTileColor() : ?string {
        return $this->msapplication ? $this->tileColor ?? $this->application->theme->color : null;
    }

    private function getBrowserconfig() : ?string {
        return $this->msapplication ? '/' . ltrim( $this->browserconfig ?? 'browserconfig.xml', '/' ) : null;
    }

    public function favicon( ?string $dir = null ) : array {

        $links = FaviconBundle::links();

        foreach ( FaviconBundle::links() as $key => $link ) {
            $url = implode( DIRECTORY_SEPARATOR, array_filter( [ $this->publicDir, $dir, $link[ 'href' ] ] ), );
            if ( !file_exists( $url ) ) {
                unset( $links[ $key ] );
            }
        }

        return $links;
    }

    /**
     *
     * * Provide a path to a stylesheet in `App/assets/styles/`.
     * * If provided with just a filename, will assume `App/assets/styles/`
     * * Or direct path to a stylesheet.
     * * If the stylesheet is not public, it will be copied to `App/public/assets/styles/`.
     *
     * @param string  ...$styles  as 'assets/styles/style.css'
     *
     * @return $this
     */
    public function addStylesheet(
        string ...$styles
    ) : self {
        foreach ( $styles as $path ) {
            // $path                = Str::contains( $path, [ '/', '\\' ] ) ? $path : "assets/styles/{$path}";
            // $this->stylesheets[] = Str::end( $path, '.css' );
            $this->stylesheet[] = new Stylesheet( $path );
        }
        return $this;
    }

    public function addScript(
        string ...$scripts
    ) : self {
        foreach ( $scripts as $path ) {
            // $path            = Str::contains( $path, [ '/', '\\' ] ) ? $path : "assets/scripts/{$path}";
            // $this->scripts[] = Str::end( $path, '.js' );
            $this->script[] = new Script( $path );
        }
        return $this;
    }

    public function addMeta(
        string $name, mixed $content,
    ) : self {
        $this->meta[ $name ] = $content;
        return $this;
    }

    private function documentPath(
        #[ExpectedValues( [ 'path', 'url', 'title', 'key' ] )]
        string $as,
    ) : ?string {
        return match ( $as ) {
            'path'  => $this->application->request->getPathInfo(),
            'url'   => $this->application->request->getHost() . $this->application->request->getPathInfo(),
            'title' => ucwords( trim( str_replace( '/', ' ', $this->application->request->getPathInfo() ) ) ),
            'key'   => Str::key( $this->application->request->getPathInfo() ),
        };
    }
}