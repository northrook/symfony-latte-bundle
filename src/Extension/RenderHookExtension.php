<?php

namespace Northrook\Symfony\Latte\Extension;

use Latte;
use Northrook\Symfony\Latte\Compiler\RuntimeHookLoader;
use Psr\Log\LoggerInterface;

// Runtime Render Hook - Injects
// Render Hook

final class RenderHookExtension extends Latte\Extension
{

    public function __construct(
        private readonly RuntimeHookLoader $hookLoader,
        private readonly ?LoggerInterface  $logger,
    ) {}

    public function getFunctions() : array {
        return [
            'render'          => [ $this, 'echoRuntimeRenderHook' ],
            'getRenderString' => [ $this, 'getRuntimeRenderHook' ],
        ];
    }

    public function echoRuntimeRenderHook( string $hook, ?string $fallback = null, bool $unique = true ) : void {
        echo $this->getRuntimeRenderHook( $hook, $fallback, $unique );
    }

    public function getRuntimeRenderHook( string $hook, ?string $fallback = null, bool $unique = true ) : ?string {
        $render = $this->hookLoader->get( $hook, $unique ) ?? $fallback;
        if ( $render ) {
            $this->logger?->debug( "Rendering hook {$hook} with {$render}" );
        }

        return $render;
    }
}