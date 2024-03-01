<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Northrook\Symfony\Latte\CoreExtension;
use Northrook\Symfony\Latte\Environment;
use Northrook\Symfony\Latte\Parameters\GlobalParameters;

return static function ( ContainerConfigurator $container ) : void {

	$fromRoot = function ( string $set = '' ) : string {
		$root = '%kernel.project_dir%' . DIRECTORY_SEPARATOR . trim(
				str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $set ), DIRECTORY_SEPARATOR,
			) . DIRECTORY_SEPARATOR;
		return strtoupper( $root );
	};

	$container->parameters()
	          ->set( 'dir.latte.templates', $fromRoot( "/templates" ) )
	          ->set( 'dir.latte.cache', $fromRoot( "/var/cache/latte" ) )
	;

	$container->services()
	          ->set( 'core.latte', Environment::class )
	          ->args( [
		                  param( 'dir.latte.templates' ),
		                  param( 'dir.latte.cache' ),
		                  service( 'core.latte.extension' )->nullOnInvalid(),
		                  service( 'logger' )->nullOnInvalid(),
		                  service( 'debug.stopwatch' )->nullOnInvalid(),
		                  service( 'core.latte.global_parameters' )->nullOnInvalid(),
	                  ] )
	          ->public()
	          ->alias( Environment::class, 'core.latte' )
	;

	$container->services()
	          ->set( 'core.latte.extension', CoreExtension::class )
	          ->args( [
		                  service( 'router' ),
		                  service( 'logger' )->nullOnInvalid(),
	                  ] )
	          ->public()
	          ->alias( CoreExtension::class, 'core.latte.extension' )
	;

	$container->services()
	          ->set( 'core.latte.global_parameters', GlobalParameters::class )
	          ->args( [
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
	                  ] )
	          ->public()
	          ->alias( GlobalParameters::class, 'core.latte.global_parameters' )
	;
};