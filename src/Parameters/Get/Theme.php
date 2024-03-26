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
    private string $hex = '#29b6fa';

    // use the [attr=>value] syntax, add media as well
    // <meta name="theme-color" media="(prefers-color-scheme: light)" content="white">
    // <meta name="theme-color" media="(prefers-color-scheme: dark)"  content="black">


    public function __get( string $name ) {
        return match ( $name ) {
            'color'  => $this->hex,
            'scheme' => $this->theme === 'system' ? 'normal' : $this->theme,
            default  => null,
        };
    }


    public function __toString() : string {
        return $this->theme;
    }


}