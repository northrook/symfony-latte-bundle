<?php

namespace Northrook\Symfony\Latte\Parameters\Document;

use Northrook\Symfony\Core\File;
use Northrook\Symfony\Latte\Parameters\Document\Manifest\Display;
use function json_encode;

// todo: https://web.dev/articles/window-controls-overlay

final class Manifest
{
    public function __construct(
        public string                $name,
        public ?string               $shortName = null,
        public ?string               $description = null,
        public array                 $icons = [],
        public ?string               $startUrl = '/',
        public ?string               $id = null,
        public Display               $display = Display::MinimalUI,
        public string                $scope = '/',
        public ?string               $themeColor = null,      // todo: use Hex or Color type when available
        public ?string               $backgroundColor = null, // todo: use Hex or Color type when available
        public null | array | string $screenshots = null,     // https://web.dev/articles/add-manifest#screenshots
        public null | array | string $shortcuts = null,       // https://web.dev/articles/add-manifest#shortcuts
    ) {}

    public function save( ?string $path = null ) : bool {

        $path = File::path( $path ?? 'dir.public/manifest.json' );

        return File::save( $path, $this->generate() );
    }

    public function generate() : string {

        $display = ( Display::OverlayUI === $this->display ) ? [
            'display_override' => [ Display::OverlayUI, Display::MinimalUI ],
            'display'          => Display::Standalone,
        ] : [ 'display' => $this->display ];

        $manifest = [
            'name'             => $this->name,
            'short_name'       => $this->shortName,
            'description'      => $this->description,
            'icons'            => $this->icons,
            'start_url'        => $this->startUrl,
            'id'               => $this->id,
            ... $display,
            'scope'            => $this->scope,
            'theme_color'      => $this->themeColor,
            'background_color' => $this->backgroundColor,
            'screenshots'      => $this->decode( $this->screenshots ),
            'shortcuts'        => $this->decode( $this->shortcuts ),
        ];

        $manifest = array_filter( $manifest );

        $json = json_encode( $manifest, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES );

        dd( $manifest, $json );

        return $json;
    }

    private function decode( null | array | string $value ) : ?array {
        return is_string( $value ) ? json_decode( $value, true ) : $value;
    }
}