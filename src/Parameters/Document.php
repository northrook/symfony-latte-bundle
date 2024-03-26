<?php

namespace Northrook\Symfony\Latte\Parameters;

use Northrook\Elements\Element\Attributes;
use Northrook\Symfony\Assets\Script;
use Northrook\Symfony\Assets\Stylesheet;
use Northrook\Symfony\Latte\Parameters\Document\Favicon;
use Northrook\Symfony\Latte\Parameters\Document\Manifest;
use Northrook\Symfony\Latte\Parameters\Document\Meta;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;


/**
 * @property string $robots
 * @property string $title
 * @property string $description
 * @property array  $scripts
 * @property array  $stylesheets
 * @property array  $meta
 */
class Document
{


    /** @var Script[] */
    protected array $script = [];

    /** @var Stylesheet[] */
    protected array $stylesheet = [];

    /** @var Meta[] */
    protected array $meta = [];

    public readonly Attributes $body;
    public readonly Manifest   $manifest;

    public function __construct(
        private readonly Application      $application,
        private readonly Content          $content,
        public readonly Favicon           $favicon,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?Stopwatch       $stopwatch = null,
    ) {

        $this->manifest = new Manifest(
            $this->application->sitename
        );

        $this->body = new Attributes(
            id          : $this->application->request->getPathInfo(),
            class       : 'test cass',
            data_strlen : strlen( $this->content->__toString() ),
        );

        foreach ( [
            'robots'      => $this->getRobots(),
            // 'canonical'   => $this->getCanonical(),
            'title'       => $this->getTitle(),
            'description' => $this->getDescription(),
            'keywords'    => $this->getKeywords(),
            'author'      => $this->getAuthor(),
        ] as $name => $content ) {
            $this->addMeta( $name, $content );
        }
    }

    public function __get( string $name ) {
        if ( isset( $this->meta[ $name ] ) ) {
            return $this->meta[ $name ];
        }


        $name = "get" . ucfirst( $name );
        if ( method_exists( $this, $name ) ) {
            return $this->$name() ?? null;
        }

        return null;
    }

    public function __set( string $name, mixed $value ) {
        $meta                      = new Meta( $name, $value );
        $this->meta[ $meta->name ] = $meta;
    }


    public function meta( ...$get ) : array {
        $return = [];

        if ( count( $get ) === 1 ) {
            $get = match ( $get[ 0 ] ) {
                'all'   => array_keys( $this->meta ),
                'info'  => [ 'title', 'description', 'keywords', 'robots', 'author' ],
                default => $get,
            };
        }

        foreach ( $get as $name ) {

            if ( !isset( $this->meta[ $name ] ) ) {
                $this->logger->warning(
                    "The {service} was requested on {route}, but was not available.",
                    [
                        'service'   => 'meta',
                        'route'     => $this->application->request->getPathInfo(),
                        'inventory' => $this->meta,
                    ],
                );
                continue;
            }

            $meta = $this->meta[ $name ];

            if ( true === $meta->printed ) {
                continue;
            }

            $return += $meta->get();
        }

        return $return;
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

    private function getTitle() {
        return __METHOD__;
    }

    private function getDescription() {
        return __METHOD__;
    }

    private function getKeywords() {
        return __METHOD__;
    }

    private function getRobots() {
        return __METHOD__;
    }

    private function getAuthor() {
        return __METHOD__;
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
    public function addStylesheet( string ...$styles ) : self {
        foreach ( $styles as $path ) {
            // $path                = Str::contains( $path, [ '/', '\\' ] ) ? $path : "assets/styles/{$path}";
            // $this->stylesheets[] = Str::end( $path, '.css' );
            $this->stylesheet[] = new Stylesheet( $path );
        }
        return $this;
    }

    public function addScript( string ...$scripts ) : self {
        foreach ( $scripts as $path ) {
            // $path            = Str::contains( $path, [ '/', '\\' ] ) ? $path : "assets/scripts/{$path}";
            // $this->scripts[] = Str::end( $path, '.js' );
            $this->script[] = new Script( $path );
        }
        return $this;
    }

    public function addMeta( string $name, string $content ) : self {
        $meta                      = new Meta( $name, $content );
        $this->meta[ $meta->name ] = $meta;
        return $this;
    }
}