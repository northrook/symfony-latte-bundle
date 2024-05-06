<?php

declare( strict_types = 1 );

namespace Northrook\Symfony\Latte\Variables;

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
class Document
{
    /**
     * List of printed meta tags.
     *
     * @var array<int, string>
     */
    private array $printed = [];

    private array $meta = [
        'content' => [ 'title' => null, 'description' => null, 'author' => null, 'keywords' => null ],
    ];

    public function __construct(
        array                  $meta,
        private array          $stylesheets,
        private array          $scripts,
        private readonly array $documentBodyAttributes,

        public bool            $manifest = true,
        // may need to be assigned to public readonly after checking for a manifest
        public bool            $msApplication = true, // same
    ) {
        $this->assignDocumentMeta( $meta );
    }

    public function __get( string $name ) : mixed {
        return match ( $name ) {
            'title'                  => $this->meta( 'title', 'content' ),
            'description'            => $this->meta( 'description', 'content' ),
            'author'                 => $this->meta( 'author', 'content' ),
            'keywords'               => $this->meta( 'keywords', 'content' ),
            'content'                => $this->meta( 'content' ),
            'robots'                 => $this->meta( 'robots' ),
            'bodyAttributes'         => $this->documentBodyAttributes,
            'stylesheets', 'scripts' => $this->assets( $name ),
            default                  => null,
        };
    }

    /**
     * {@see Document} does not support dynamic properties.
     */
    public function __set( string $name, $value ) : void {}

    public function __isset( string $name ) : bool {
        return array_key_exists( $name, $this->meta );
    }

    public function meta( ?string $get = null, ?string $group = null ) : mixed {

        $array = $group ? $this->meta[ $group ] ?? [] : $this->meta;

        if ( $get && array_key_exists( $get, $array ) ) {
            $array = $array[ $get ];
        }

        $meta = [];

        if ( is_string( $array ) ) {
            return $array;
        }

        foreach ( $array as $name => $content ) {
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

    private function printed( string $name ) : bool {

        return array_key_exists( $name, $this->printed );

        // $this->printed[ $name ] = $value;
        // dump( $name, $value );
        // return $value;
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
            }
        }

        return $this->{$type};
    }

    private function assignDocumentMeta( ?array $meta = null ) : void {

        // $meta = array_merge( $this->meta['content'], $meta );

        foreach ( $meta as $name => $value ) {
            if ( array_key_exists( $name, $this->meta[ 'content' ] ) ) {
                $this->meta[ 'content' ][ $name ] = $value;
            }
            else {
                $this->meta[ $name ] = $value;
            }
        }

    }
}