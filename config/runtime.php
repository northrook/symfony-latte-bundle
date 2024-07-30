<?php

declare( strict_types = 1 );

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

//--------------------------------------------------------------------
// Runtime Application Accessor
//--------------------------------------------------------------------

use Northrook\Symfony\Latte\Compiler\RuntimeHookLoader;
use Northrook\Symfony\Latte\DependencyInjection\UrlGeneratorExtension;
use Northrook\Symfony\Latte\Environment;
use Northrook\Symfony\Latte\Extension\CoreExtension;
use Northrook\Symfony\Latte\Extension\RenderHookExtension;
use Northrook\Symfony\Latte\Runtime\App;

return static function ( ContainerConfigurator $container ) : void {

    $services = $container->services();

    $services->set( App::class )
             ->args(
                 [
                     param( 'kernel.environment' ),
                     param( 'kernel.debug' ),
                     service( 'request_stack' ),
                     service( 'security.token_storage' ),
                     service( 'security.csrf.token_manager' ),
                 ],
             );

    $services->set( UrlGeneratorExtension::class )
             ->args( [ service( 'router' ) ] );


    // $services->set( 'latte.environment', Environment::class )
    //          ->args(
    //              [
    //                  param( 'dir.latte.cache' ),
    //                  param( 'latte.global_variable.key' ),
    //                  service( 'latte.global_variable' ),
    //                  service( 'latte.extension.core' ),
    //                  service( 'latte.extension.hook' ),
    //                  service( 'latte.hook.loader' ),
    //                  service( 'parameter_bag' ),
    //                  service( 'logger' )->nullOnInvalid(),
    //                  service( 'debug.stopwatch' )->nullOnInvalid(),
    //              ],
    //          )
    //          ->call( 'addGlobalVariable', [] )
    //          ->call( 'addExtension', );
};