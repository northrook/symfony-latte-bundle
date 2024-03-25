<?php

namespace Northrook\Symfony\Latte\Parameters\Document;

class Favicon
{
    public readonly array  $link;
    public readonly string $tileColor;
    public readonly string $browserconfig;

    public function __construct() {
        $this->tileColor     = '#ff2d20';
        $this->browserconfig = '/img/favicon/browserconfig.xml';
        $this->link          = [
            [
                'rel'   => "apple-touch-icon",
                'sizes' => "180x180",
                'href'  => "/img/favicon/apple-touch-icon.png",
            ],
            [
                'rel'   => "icon",
                'type'  => "image/png",
                'sizes' => "32x32",
                'href'  => "/img/favicon/favicon-32x32.png",
            ],
            [
                'rel'   => "icon",
                'type'  => "image/png",
                'sizes' => "16x16",
                'href'  => "/img/favicon/favicon-16x16.png",
            ],
            [
                'rel'  => "manifest",
                'href' => "/img/favicon/site.webmanifest",
            ],
            [
                'rel'   => "mask-icon",
                'href'  => "/img/favicon/safari-pinned-tab.svg",
                'color' => "#ff2d20",
            ],
            [
                'rel'  => "shortcut icon",
                'href' => "/img/favicon/favicon.ico",
            ],
            [
                'name'    => "msapplication-TileColor",
                'content' => "#ff2d20",
            ],
            [
                'name'    => "msapplication-config",
                'content' => "/img/favicon/browserconfig.xml",
            ],
        ];;
    }

}