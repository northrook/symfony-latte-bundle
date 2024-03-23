<?php

namespace Northrook\Symfony\Latte\Parameters\Get;

use Stringable;

/**
 * @property string $color
 * @property string $scheme
 */
class Theme implements Stringable
{
    private string $theme = 'system';
    // private string $mode  = 'system';
    private string $color = '#29b6fa';


    public function __get( string $name ) {
        return match ( $name ) {
            'color'  => $this->color,
            'scheme' => $this->theme === 'system' ? 'normal' : $this->theme,
            default  => null,
        };
    }


    public function __toString() : string {
        return $this->theme;
    }


}