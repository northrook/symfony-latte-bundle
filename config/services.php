<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;


use Northrook\Core\Cache;
use Northrook\Symfony\Latte\Compiler\RuntimeHookLoader;
use Northrook\Symfony\Latte\Environment;
use Northrook\Symfony\Latte\Extension\CoreExtension;
use Northrook\Symfony\Latte\Extension\RenderHookExtension;
use Northrook\Symfony\Latte\Variables\Application;
use Northrook\Symfony\Latte\Variables\Application\ApplicationDependencies;

return static function ( ContainerConfigurator $container ) : void {

    $services = $container->services();

    $fromRoot = static fn ( string $set = '' ) => '%kernel.project_dir%' . DIRECTORY_SEPARATOR . trim(
            str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $set ), DIRECTORY_SEPARATOR,
        ) . DIRECTORY_SEPARATOR;


    // Parameters
    $container->parameters()
              ->set( 'latte.parameter_key.global', 'get' )
              ->set( 'latte.parameter_key.document', 'document' )
              ->set( 'dir.latte.templates', $fromRoot( "/templates" ) )
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
                     param( 'latte.parameter_key.global' ),
                     param( 'latte.parameter_key.document' ),
                     service( 'latte.parameters.application' ),
                     service( 'latte.extension.core' ),
                     service( 'latte.extension.hook' ),
                     service( 'latte.hook.loader' ),
                     service( 'parameter_bag' ),
                     service( 'logger' )->nullOnInvalid(),
                     service( 'debug.stopwatch' )->nullOnInvalid(),
                 ],
             );

    //--------------------------------------------------------------------
    // Global Variable
    //--------------------------------------------------------------------

    $services->set( 'latte.parameters.dependencies', ApplicationDependencies::class )
             ->args(
                 [
                     service_closure( 'request_stack' ),               // RequestStack
                     service_closure( 'router' ),
                     service_closure( 'security.token_storage' )->nullOnInvalid(),
                     service_closure( 'translation.locale_switcher' )->nullOnInvalid(),
                     service_closure( 'security.csrf.token_generator' )->nullOnInvalid(),
                     service_closure( 'logger' )->nullOnInvalid(),
                 ],
             );

    $services->set( 'latte.parameters.application', Application::class )
             ->args(
                 [
                     param( 'kernel.environment' ),               // Environment<string>
                     param( 'kernel.debug' ),                     // Debug<bool>
                     inline_service( Cache::class ),
                     service( 'latte.parameters.dependencies' ),
                 ],
             );
};