<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

//use Northrook\Symfony\Latte\Services\EnvironmentService;


use Northrook\Symfony\Latte\LatteBundleExtension;
use Northrook\Symfony\Latte\LatteEnvironment;
use Northrook\Symfony\Latte\Parameters\CoreParameters;

return static function ( ContainerConfigurator $container ) : void {

    $fromRoot = function ( string $set = '' ) : string {
        return '%kernel.project_dir%' . DIRECTORY_SEPARATOR . trim(
                str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $set ), DIRECTORY_SEPARATOR,
            ) . DIRECTORY_SEPARATOR;
    };

    // Parameters
    $container->parameters()
              ->set( 'dir.latte.templates', $fromRoot( "/templates" ) )
              ->set( 'dir.latte.cache', $fromRoot( "/var/cache/latte" ) )
    ;

    // Services
    $container->services()
        //
        // ☕ - Latte Environment
              ->set( 'latte.environment', LatteEnvironment::class )
              ->args(
                  [
                      param( 'dir.latte.templates' ),
                      param( 'dir.latte.cache' ),
                      service( 'latte.core.extension' ),
                      service( 'logger' )->nullOnInvalid(),
                      service( 'debug.stopwatch' )->nullOnInvalid(),
                      service( 'latte.core.parameters' )->nullOnInvalid(),
                  ],
              )
              ->public()
              ->alias( LatteEnvironment::class, 'latte.environment' )
        //
        // 🧩️ - Latte Extension
              ->set( 'latte.core.extension', LatteBundleExtension::class )
              ->args(
                  [
                      service( 'router' ),
                      service( 'logger' )->nullOnInvalid(),
                  ],
              )
              ->alias( LatteBundleExtension::class, 'latte.core.extension' )
        //
        // ️📦️ - Global Parameters
              ->set( 'latte.core.parameters', CoreParameters::class )
              ->args(
                  [
                      param( 'kernel.environment' ),               // Environment<string>
                      param( 'kernel.debug' ),                     // Debug<bool>
                      service( 'request_stack' ),               // RequestStack
                      service( 'router' ),                      // UrlGeneratorInterface
                      service( 'security.token_storage' )       // TokenStorageInterface
                      ->nullOnInvalid(),
                      service( 'translation.locale_switcher' )  // LocaleSwitcher
                      ->nullOnInvalid(),
                      service( 'logger' )                       // LoggerInterface
                      ->nullOnInvalid(),
                  ],
              )
              ->autowire()
              ->public()
              ->alias( CoreParameters::class, 'latte.core.parameters' )
    ;
};