<?php

namespace Northrook\Symfony\Latte\Compiler;

use Stringable;

final class RuntimeHookLoader
{
    /** @var array<string, null|string|Stringable> */
    private array $hooks = [];

    /** @var array<string, int> */
    private array $rendered = [];


    public function get( string $hook, bool $force = false ) : ?string {

        if ( !$force || isset( $this->rendered[ $hook ] ) ) {
            return null;
        }

        $this->rendered[ $hook ] = ( $this->rendered[ $hook ] ?? 0 ) + 1;

        return $this->rendered[ $hook ];
    }

    public function addHook( string $hook, string $class ) : void {
        $this->hooks[ $hook ] = $class;
    }

    public function getHooks() : array {
        return $this->hooks;
    }
}