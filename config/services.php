<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;


use Northrook\Symfony\Latte\Core;
use Northrook\Symfony\Latte\GlobalVariable;

return static function ( ContainerConfigurator $container ) : void {

    $services = $container->services();

    $fromRoot = static fn ( string $set = '' ) => '%kernel.project_dir%' . DIRECTORY_SEPARATOR . trim(
            str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $set ), DIRECTORY_SEPARATOR,
        ) . DIRECTORY_SEPARATOR;


    // Parameters
    $container->parameters()
              ->set( 'latte.parameter_key.application', 'get' )
              ->set( 'dir.latte.templates', $fromRoot( "/templates" ) )
              ->set( 'dir.latte.cache', $fromRoot( "/var/cache/latte" ) );

    //--------------------------------------------------------------------
    // Global Variable
    //--------------------------------------------------------------------
    $services->set( 'latte.parameters.global', GlobalVariable::class )
             ->args(
                 [
                     param( 'kernel.environment' ),               // Environment<string>
                     param( 'kernel.debug' ),                     // Debug<bool>
                     service_closure( 'request_stack' ),               // RequestStack
                     service_closure( 'router' ),
                     service_closure( 'security.token_storage' )->nullOnInvalid(),
                     service_closure( 'translation.locale_switcher' )->nullOnInvalid(),
                     service_closure( 'security.csrf.token_generator' )->nullOnInvalid(),
                     service_closure( 'logger' )->nullOnInvalid(),
                 ],
             )
             ->autowire()
             ->public()
             ->alias( GlobalVariable::class, 'latte.parameters.global' );

    // Services
    $container->services()
        //
        // ☕ - Latte Environment
              ->set( 'latte.environment', Core\Environment::class )
              ->call(
                  'dependencyInjection',
                  [
                      service( 'parameter_bag' ),
                      service( 'ApplicationParameters' ),
                      service( 'logger' )->nullOnInvalid(),
                      service( 'debug.stopwatch' )->nullOnInvalid(),
                  ],
              )
              ->call( 'addExtension', [ service( 'latte.core.extension' ) ] )
              ->autowire()
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
              ->set( 'latte.parameters.application', GlobalVariable::class )
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
                      service( 'security.csrf.token_generator' ) // CSRF Token Generator
                      ->nullOnInvalid(),
                      service( 'logger' )                       // LoggerInterface
                      ->nullOnInvalid(),
                  ],
              )
              ->autowire()
              ->public()
              ->alias( GlobalVariable::class, 'latte.parameters.application' );
};