<?php

namespace Northrook\Symfony\Latte\Parameters\Document;

use Northrook\Symfony\Core\File;
use Northrook\Symfony\Latte\Core\IcoMaker;

class Favicon
{
    public readonly array  $link;
    public readonly string $tileColor;
    public readonly string $browserconfig;

    public function __construct() {

        $ico = File::path( 'dir.public/assets/img/favicon/favicon.ico' );

        if ( !$ico->exists ) {
            $make = new IcoMaker( __DIR__ . 'icon.png', [ [ 256, 256 ], [ 64, 64 ] ] );
            $make->save_ico( $ico->value );
        }

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
                'rel'   => "mask-icon",
                'href'  => "/img/favicon/safari-pinned-tab.svg",
                'color' => "#ff2d20",
            ],
            [
                'rel'  => "shortcut icon",
                'href' => "/assets/img/favicon/favicon.ico",
            ],
        ];
    }

}