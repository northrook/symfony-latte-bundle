<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

//use Northrook\Symfony\Latte\Services\EnvironmentService;


use Northrook\Symfony\Latte\CoreExtension;
use Northrook\Symfony\Latte\Environment;

return static function ( ContainerConfigurator $container ) : void {

	$fromRoot = function( string $set = '' ) : string {
		return '%kernel.project_dir%' . DIRECTORY_SEPARATOR . trim(
				str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $set ), DIRECTORY_SEPARATOR,
			) . DIRECTORY_SEPARATOR;
	};

	$container->parameters()
	          ->set( 'dir.templates', $fromRoot( "/templates" ) )
	          ->set( 'dir.cache.latte', $fromRoot( "/var/cache/latte" ) )
	;

	$container->services()
	          ->set( 'core.latte', Environment::class )
	          ->args( [
		                  param( 'dir.templates' ),
		                  param( 'dir.cache.latte' ),
		                  service( 'logger' )->nullOnInvalid(),
		                  service( 'debug.stopwatch' )->nullOnInvalid(),
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
};