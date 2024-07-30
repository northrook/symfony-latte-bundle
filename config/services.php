<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;


use Northrook\Symfony\Latte\Compiler\RuntimeHookLoader;
use Northrook\Symfony\Latte\Environment;
use Northrook\Symfony\Latte\Extension\CoreExtension;
use Northrook\Symfony\Latte\Extension\RenderHookExtension;

return static function ( ContainerConfigurator $container ) : void {

    $services = $container->services();

    // Parameters
    $container->parameters()
              ->set( 'dir.latte.templates', '%kernel.project_dir%/templates' )
              ->set( 'dir.latte.cache', '%kernel.cache_dir%/latte' );

    //--------------------------------------------------------------------
    // Latte Environment
    //--------------------------------------------------------------------

    $services->set( 'latte.hook.loader', RuntimeHookLoader::class )
             ->args( [ inline_service( Cache::class ) ] );

    $services->set( 'latte.extension.core', CoreExtension::class )
             ->args(
                 [
                     service( 'router' ),
                     service( 'logger' )->nullOnInvalid(),
                 ],
             );

    $services->set( 'latte.extension.hook', RenderHookExtension::class )
             ->args(
                 [
                     service( 'latte.hook.loader' ),
                     service( 'logger' )->nullOnInvalid(),
                 ],
             );

    $services->set( 'latte.environment', Environment::class )
             ->args(
                 [
                     param( 'dir.latte.cache' ),
                     param( 'latte.global_variable.key' ),
                     service( 'latte.global_variable' ),
                     service( 'latte.extension.core' ),
                     service( 'latte.extension.hook' ),
                     service( 'latte.hook.loader' ),
                     service( 'parameter_bag' ),
                     service( 'logger' )->nullOnInvalid(),
                     service( 'debug.stopwatch' )->nullOnInvalid(),
                 ],
             );
    $services->set( 'latte.extension.hook', RenderHookExtension::class )
             ->args(
                 [
                     service( 'latte.hook.loader' ),
                     service( 'logger' )->nullOnInvalid(),
                 ],
             );
};