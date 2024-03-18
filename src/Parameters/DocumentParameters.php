<?php

namespace Northrook\Symfony\Latte\Parameters;

// TODO : Option to add parameters from Controller or via config
// TODO : Asset Manager from Components Library

/**
 * @property string $base
 */
class DocumentParameters
{
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