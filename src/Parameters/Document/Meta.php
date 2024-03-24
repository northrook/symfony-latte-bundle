<?php

declare( strict_types = 1 );

namespace Northrook\Symfony\Latte\Parameters\Type;

use Northrook\Support\Str;

/**
 * @property string $name
 * @property string $content
 * @property bool   $printed
 */
final class Meta
{
    private string $name;
    private string $content;
    private bool   $printed = false;

    public function __construct(
        string                  $name,
        string                  $content,
        public readonly ?string $group = null,
    ) {
        $this->name    = Str::key( $name );
        $this->content = Str::sanitize( $content );
    }

    public function __get( string $name ) {
        if ( property_exists( $this, $name ) ) {
            return $this->$name;
        }
        return null;
    }

    public function print() : void {
        echo "<meta name=\"$this->name\" content=\"$this->content\">";
    }

    public function __toString() : string {
        $this->printed = true;
        return $this->content;
    }

    public function get() : array {
        $this->printed = true;
        return [
            $this->name => $this->content,
        ];
    }

}