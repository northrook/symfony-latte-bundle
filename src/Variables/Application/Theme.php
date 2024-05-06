<?php

namespace Northrook\Symfony\Latte\Variables\Application;

final readonly class Theme
{


    public readonly string $color;

    // <meta name="theme-color" media="(prefers-color-scheme: light)" content="white">
    // <meta name="theme-color" media="(prefers-color-scheme: dark)"  content="black">

    public function __construct(
        public string  $scheme = 'system',
        private string $hex = '#29b6fa',
    ) {
        $this->color = $this->hex;
    }

    public function __toString() : string {
        return $this->scheme;
    }

}