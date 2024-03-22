<?php

namespace Northrook\Symfony\Latte\Parameters;

use Northrook\Elements\Body;
use Northrook\Support\Str;
use Northrook\Symfony\Latte\Parameters\Content\Meta;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;


/**
 * @property array $scripts
 * @property array $stylesheets
 * @property array $meta
 */
class Document
{


    protected array $scripts     = [];
    protected array $stylesheets = [];

    /** @var Meta[] */
    protected array $meta = [];

    public readonly Body $body;
    public string        $title = __METHOD__;

    public function __construct(
        private readonly Application      $application,
        private readonly Content          $content,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?Stopwatch       $stopwatch = null,
    ) {

        $this->body = new Body(
            id          : $this->application->request->getPathInfo(),
            class       : 'test cass',
            data_strlen : strlen( $this->content->__toString() ),
        );

        foreach ( [
            'title'       => $this->getTitle(),
            'description' => $this->getDescription(),
            'keywords'    => $this->getKeywords(),
            'robots'      => $this->getRobots(),
            'author'      => $this->getAuthor(),
        ] as $name => $content ) {
            $this->addMeta( $name, $content );
        }
    }

    public function __get( string $name ) {
        $name = "get" . ucfirst( $name );
        if ( method_exists( $this, $name ) ) {
            return $this->$name() ?? null;
        }

        if ( isset( $this->meta[ $name ] ) ) {
            return $this->meta[ $name ];
        }

        return null;
    }

    public function meta( ...$get ) : array {
        $return = [];

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

            $return[ $name ] = $meta->get();
            $meta->printed   = true;
        }

        return $return;
    }

    private function getMeta() : array {
        return $this->meta;
    }

    private function getScripts() : array {
        return $this->scripts;
    }

    private function getStylesheets() : array {
        return $this->stylesheets;
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
            $path                = Str::contains( $path, [ '/', '\\' ] ) ? $path : "assets/styles/{$path}";
            $this->stylesheets[] = Str::end( $path, '.css' );
        }
        return $this;
    }

    public function addScript( string ...$scripts ) : self {
        foreach ( $scripts as $path ) {
            $path            = Str::contains( $path, [ '/', '\\' ] ) ? $path : "assets/scripts/{$path}";
            $this->scripts[] = Str::end( $path, '.js' );
        }
        return $this;
    }

    public function addMeta( string $name, string $content ) : self {
        $meta                      = new Meta( $name, $content );
        $this->meta[ $meta->name ] = $meta;
        return $this;
    }
}