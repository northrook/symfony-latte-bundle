<?php

namespace Northrook\Symfony\Latte\Parameters;

use Northrook\Favicon\FaviconBundle;
use Northrook\Symfony\Core\File;


/**
 * @property-read ?string $title
 * @property-read ?string $description
 * @property-read ?string $author
 * @property-read ?string $keywords
 * @property-read array   $robots
 * @property-read array   $content
 * @property-read array   $stylesheets
 * @property-read array   $scripts
 * @property-read array   $bodyAttributes
 */
final class Document
{

    private const CONTENT_META = [ 'title' => null, 'description' => null, 'author' => null, 'keywords' => null ];

    private array $printed = [];

    private array $meta = [
        'content' => self::CONTENT_META,
    ];


    public function __construct(
        array                  $meta,
        private readonly array $stylesheets,
        private readonly array $scripts,
        private readonly array $documentBodyAttributes,
        public bool            $manifest = true,
        public bool            $msApplication = true,
    ) {
        $this->assignDocumentMeta( $meta );
    }

    private function assignDocumentMeta( ?array $meta = null ) : void {

        $meta = array_merge( Document::CONTENT_META, $meta );

        foreach ( $meta as $name => $value ) {
            if ( array_key_exists( $name, Document::CONTENT_META ) ) {
                $this->meta[ 'content' ][ $name ] = $value;
            }
            else {
                $this->meta[ $name ] = $value;
            }
        }

    }

    public function meta( ?string $get = null ) : array {
        $meta         = [];
        $documentMeta = $get ? $this->meta[ $get ] ?? [] : $this->meta;

        foreach ( $documentMeta as $name => $content ) {

            if ( $this->printed( $name ) ) {
                continue;
            }

            if ( is_array( $content ) ) {
                foreach ( $this->metaGroup( $content ) as $value ) {
                    $meta[] = $value;
                }
            }
            if ( is_string( $content ) ) {
                $meta[] = $this->metaValue( $name, $content );
            }


        }

        return $meta;
    }

    private function metaValue(
        string                $name,
        null | string | array $content = null,
    ) : ?array {

        $content ??= $this->meta[ $name ] ?? null;

        if ( is_array( $content ) ) {
            $content = $this->metaGroup( $content );
        }
        else {
            $content = [ 'name' => $name, 'content' => $content ];
        }

        $this->printed[ $name ] = $content;

        return $content[ 'content' ] ?? null ? $content : [];
    }

    private function metaGroup( array $value ) : array {

        $meta = [];
        foreach ( $value as $name => $content ) {
            $meta[] = $this->metaValue( $name, $content );
        }

        return $meta;
    }

    public function metaOld( ...$get ) : array {
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
            else {
                if ( property_exists( $this, $name ) ) {
                    $value = $this->$name;
                }
                else {
                    $method = "get" . ucfirst( $name );
                    if ( method_exists( $this, $method ) ) {
                        $value = $this->$method();
                    }
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

    public function unset( string $meta ) : self {
        unset( $this->meta[ $meta ] );

        return $this;
    }

    private function assets( string $type ) : array {


        if ( !property_exists( $this, $type ) ) {
            return [];
        }

        $assets = [];

        foreach ( $this->{$type} as $index => $asset ) {
            foreach ( $asset as $key => $value ) {
                if ( $value instanceof \Stringable ) {
                    $this->{$type}[ $index ][ $key ] = (string) $value;
                }
                if ( is_bool( $value ) ) {
                    // $this->{$type}[ $index ][ $key ] = $value ? true : 'false';
                }
            }
        }

        return $this->{$type};
    }

    public function __get( string $name ) {

        if ( array_key_exists( $name, $this->printed ) ) {
            return null;
        }

        if ( array_key_exists( $name, $this::CONTENT_META ) && ( $this->meta[ 'content' ] ?? null ) ) {
            return $this->metaValue( $name, $this->meta[ 'content' ][ $name ] )[ 'content' ] ?? null;
        }

        return match ( $name ) {
            'content'                => $this->meta( 'content' ),
            'robots'                 => $this->meta( 'robots' ),
            'bodyAttributes'         => $this->documentBodyAttributes,
            'stylesheets', 'scripts' => $this->assets( $name ),
            default                  => null
        };
    }

    public function __set( string $name, mixed $value ) {
        return;
    }

    private function printed( string $name ) : bool {

        return array_key_exists( $name, $this->printed );

        // $this->printed[ $name ] = $value;
        // dump( $name, $value );
        // return $value;
    }

    public function __isset( string $name ) {
        return isset( $this->$name );
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
            $url =
                implode( DIRECTORY_SEPARATOR, array_filter( [ File::path( 'dir.public' ), $dir, $link[ 'href' ] ] ), );
            if ( !file_exists( $url ) ) {
                unset( $links[ $key ] );
            }
        }

        // var_dump($links);

        return $links;
    }
}