<?php

namespace Northrook\Symfony\Latte\Core;

use JetBrains\PhpStorm\ExpectedValues;
use Northrook\Support\Str;
use Northrook\Types\Interfaces\Printable;
use Northrook\Types\Path;
use Stringable;

/**
 * @property bool $printed
 */
abstract class Asset implements Printable, Stringable
{

    protected bool         $printed = false;
    public readonly string $name;
    public readonly Path   $path;
    public readonly string $version;
    public readonly string $mimeType;

    public function __construct(
        Path | string $path,       // pass origin, not public
        ?string       $name = null,// if null, use the file name
        private array $attributes = [],
    ) {
        $this->path     = new Path( $path );
        $this->name     = $name ?? $this->path->filename;
        $this->version  = $this->version();
        $this->mimeType = mime_content_type( $this->path );
    }

    public function __get( string $name ) {
        if ( property_exists( $this, $name ) ) {
            return $this->$name;
        }
        return null;
    }

    protected function version(
        #[ExpectedValues( 'lastModified', 'contentHash', 'timestamp' )]
        string $by = 'lastModified',
    ) : string {
        return match ( $by ) {
            'lastModified' => filemtime( $this->path ),
            'contentHash'  => crc32( file_get_contents( $this->path ) ),
            default        => time()
        };
    }

    protected function attributes() : array {
        foreach ( $this->attributes as $key => $value ) {
            $name                     = Str::key( $key );
            $value                    = Str::sanitize( $value );
            $this->attributes[ $key ] = "$name=\"$value\"";
        }

        return $this->attributes;
    }

    public function __toString() : string {
        $this->printed = true;
        return "$this->path?v=$this->version";
    }
}