<?php

namespace Northrook\Symfony\Latte\Parameters;

class Document
{
    public string $title = __METHOD__;
    
    public function __get( string $name ) {
        $name = "get" . ucfirst( $name );
        if ( method_exists( $this, $name ) ) {
            return $this->$name() ?? null;
        }

        return null;
    }

    protected function getBase() : string {
        return __DIR__ . '/document.latte';
    }
}