<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Northrook\Symfony\Latte\CoreExtension;
use Northrook\Symfony\Latte\Environment;
use Northrook\Symfony\Latte\Parameters;
use Northrook\Symfony\Latte\Parameters\Document;

return static function ( ContainerConfigurator $container ) : void {

    $fromRoot = function ( string $set = '' ) : string {
        return '%kernel.project_dir%' . DIRECTORY_SEPARATOR . trim(
                str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $set ), DIRECTORY_SEPARATOR,
            ) . DIRECTORY_SEPARATOR;
    };

    // Parameters
    $container->parameters()
              ->set( 'latte.global_parameters_key', 'get' )
              ->set( 'dir.latte.templates', $fromRoot( "/templates" ) )
              ->set( 'dir.latte.cache', $fromRoot( "/var/cache/latte" ) )
    ;

    // Services
    $container->services()
        //
        // ☕ - Latte Environment
              ->set( 'latte.environment', Environment::class )
              ->args(
                  [
                      service( 'parameter_bag' ),
                      service( 'latte.core.extension' ),
                      service( 'latte.parameters.global' ),
                      service( 'logger' )->nullOnInvalid(),
                      service( 'debug.stopwatch' )->nullOnInvalid(),
                  ],
              )
              ->alias( Environment::class, 'latte.environment' )
        //
        // 🧩️ - Latte Extension
              ->set( 'latte.core.extension', CoreExtension::class )
              ->args(
                  [
                      service( 'router' ),
                      service( 'logger' )->nullOnInvalid(),
                  ],
              )
              ->alias( CoreExtension::class, 'latte.core.extension' )
        //
        // ️📦️ - Global Parameters
              ->set( 'latte.parameters.global', Parameters::class )
              ->args(
                  [
                      param( 'kernel.environment' ),               // Environment<string>
                      param( 'kernel.debug' ),                     // Debug<bool>
                      service( 'latte.parameters.document' ),               // RequestStack
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
              ->alias( Parameters::class, 'latte.parameters.global' )
        //
        //
        // ☕ - Document Parameters
              ->set( 'latte.parameters.document', Document::class )
        //              ->args(
//                  [
//                      service( 'core.service.request' ),
//                      service( 'core.service.content' ),
//                      service( 'core.service.pathfinder' ),
//                      service( 'logger' )->nullOnInvalid(),
//                  ],
//              )
              ->alias( Document::class, 'latte.parameters.document' )
    ;
};