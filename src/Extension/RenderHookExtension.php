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

    public function echoRuntimeRenderHook( string $hook, ?string $fallback = null ) : void {
        echo $this->getRuntimeRenderHook( $hook, $fallback );
    }

    public function getRuntimeRenderHook( string $hook, ?string $fallback = null ) : ?string {
        $render = $this->hookLoader->get( $hook ) ?? $fallback;
        if ( $render ) {
            $this->logger?->debug( "Rendering hook {$hook} with {$render}" );
        }
        return $render;
    }
}