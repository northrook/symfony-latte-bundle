<?php

namespace Northrook\Symfony\Latte\Parameters;

use Northrook\Elements\Body;
use Northrook\Support\Str;
use Northrook\Symfony\Core\Services\PathfinderService;
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
    protected array $meta        = [];

    public readonly Body $body;
    public string        $title = __METHOD__;

    public function __construct(
        private readonly Application       $application,
        private readonly Content           $content,
        private readonly PathfinderService $path,
        private readonly ?Stopwatch        $stopwatch = null,
    ) {

        $this->body = new Body(
            id          : $this->application->request->getPathInfo(),
            class       : 'test cass',
            data_strlen : strlen( $this->content->__toString() ),
        );
    }


    public function __get( string $name ) {
        $name = "get" . ucfirst( $name );
        if ( method_exists( $this, $name ) ) {
            return $this->$name() ?? null;
        }

        return null;
    }

    private function getScripts() : array {
        return $this->scripts;
    }

    private function getStylesheets() : array {
        return $this->stylesheets;
    }

    private function getMeta() : array {
        return $this->meta;
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
}