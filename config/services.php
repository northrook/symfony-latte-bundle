<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

//use Northrook\Symfony\Latte\Services\EnvironmentService;


use Northrook\Symfony\Latte\CoreExtension;
use Northrook\Symfony\Latte\Environment;
use Northrook\Symfony\Latte\Parameters\GlobalParameters;
use Northrook\Symfony\Latte\Template;

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
						  service( 'core.latte.extension' )->nullOnInvalid(),
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

	$container->services()
	          ->set( 'core.latte.global_parameters', GlobalParameters::class )
	          ->args( [
		                  service( 'request_stack' ),
		                  service( 'router' ),
		                  service( 'security.csrf.token_generator' )->nullOnInvalid(),
		                  service( 'logger' )->nullOnInvalid(),
	                  ] )
	          ->public()
	          ->alias( GlobalParameters::class, 'core.latte.global_parameters' )
	;

//	$container->services()
//	          ->set( 'core.latte.template', Template::class )
//	          ->args( [
//		                  service( 'core.latte.global_parameters' ),
//	                  ] )
//	          ->public()
//	          ->alias( Template::class, 'core.latte.template' )
//	;
};