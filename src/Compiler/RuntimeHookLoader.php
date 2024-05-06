<?php

namespace Northrook\Symfony\Latte\Compiler;

use Northrook\Core\Cache;
use Stringable;

final class RuntimeHookLoader
{
    /** @var array<string, null|string|Stringable> */
    private array $hooks = [];

    /** @var array<string, int> */
    private array $rendered = [];

    public function __construct(
        private readonly Cache $cache,
    ) {}

    /**
     * @param string  $hook
     * @param bool    $unique
     * @param bool    $returnObject
     *
     * @return null|string
     */
    public function get( string $hook, bool $unique = true, bool $returnObject = false ) : ?string {

        if ( ( $unique && isset( $this->rendered[ $hook ] ) ) || !isset( $this->hooks[ $hook ] ) ) {
            return null;
        }

        $this->rendered[ $hook ] = ( $this->rendered[ $hook ] ?? 0 ) + 1;

        if ( $returnObject ) {
            return $this->hooks[ $hook ] ?? null;
        }

        if ( $this->cache->has( $hook ) ) {
            return $this->cache->get( $hook );
        }

        return $this->cache->value( $hook, (string) $this->hooks[ $hook ] );
    }

    public function addHook( string $hook, string | Stringable $string ) : void {
        $this->hooks[ $hook ] = $string;
    }

    public function getHooks() : array {
        return $this->hooks;
    }
}