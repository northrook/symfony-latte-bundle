<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;


use Northrook\Symfony\Latte\Core;
use Northrook\Symfony\Latte\Parameters;

return static function ( ContainerConfigurator $container ) : void {

    $fromRoot = function ( string $set = '' ) : string {
        return '%kernel.project_dir%' . DIRECTORY_SEPARATOR . trim(
                str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $set ), DIRECTORY_SEPARATOR,
            ) . DIRECTORY_SEPARATOR;
    };

    // Parameters
    $container->parameters()
              ->set( 'latte.parameter_key.application', 'get' )
              ->set( 'latte.parameter_key.content', 'content' )
              ->set( 'latte.parameter_key.document', 'document' )
              ->set( 'dir.latte.templates', $fromRoot( "/templates" ) )
              ->set( 'dir.latte.cache', $fromRoot( "/var/cache/latte" ) )
    ;

    // Services
    $container->services()
        //
        // ☕ - Latte Environment
              ->set( 'latte.environment', Core\Environment::class )
              ->args(
                  [
                      service( 'parameter_bag' ),
                      service( 'latte.core.extension' ),
                      service( 'latte.parameters.application' ),
                      service( 'logger' )->nullOnInvalid(),
                      service( 'debug.stopwatch' )->nullOnInvalid(),
                  ],
              )
              ->alias( Core\Environment::class, 'latte.environment' )
        //
        // 🧩️ - Latte Extension
              ->set( 'latte.core.extension', Core\Extension::class )
              ->args(
                  [
                      service( 'router' ),
                      service( 'logger' )->nullOnInvalid(),
                  ],
              )
              ->alias( Core\Extension::class, 'latte.core.extension' )
        //
        // ️📦️ - Global Parameters
              ->set( 'latte.parameters.application', Parameters\Application::class )
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
              ->alias( Parameters\Application::class, 'latte.parameters.application' )
        //
        //
        // ☕ - Document Parameters
              ->set( 'latte.parameters.content', Parameters\Content::class )
              ->autowire()
              ->public()
              ->alias( Parameters\Content::class, 'latte.parameters.content' )
        //
        //
        // ☕ - Document Parameters
              ->set( 'latte.parameters.document', Parameters\Document::class )
        //              ->args(
        //                  [
        //                      service( 'core.service.request' ),
        //                      service( 'core.service.content' ),
        //                      service( 'core.service.pathfinder' ),
        //                      service( 'logger' )->nullOnInvalid(),
        //                  ],
        //              )
              ->autowire()
              ->public()
              ->alias( Parameters\Document::class, 'latte.parameters.document' )
    ;
};