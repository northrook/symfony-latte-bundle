<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

//use Northrook\Symfony\Latte\Services\EnvironmentService;

function fromRoot( string $set = '' ) : string {
	return '%kernel.project_dir%' . DIRECTORY_SEPARATOR . trim(
			str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $set ), DIRECTORY_SEPARATOR,
		) . DIRECTORY_SEPARATOR;
}

return static function ( ContainerConfigurator $container ) : void {

	$container->parameters()
	          ->set( 'dir.templates', fromRoot( "/templates" ) )
	          ->set( 'dir.cache.latte', fromRoot( "/var/cache/latte" ) )
	;
};