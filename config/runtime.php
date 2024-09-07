<?php

declare( strict_types = 1 );

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

//--------------------------------------------------------------------
// Runtime Application Accessor
//--------------------------------------------------------------------

use Northrook\Symfony\Latte\DependencyInjection\UrlGeneratorExtension;
use Northrook\Symfony\Latte\Runtime\App;


return static function( ContainerConfigurator $container ) : void
{
    $services = $container->services();

    $services
        ->set( App::class )
        ->args(
            [
                param( 'kernel.environment' ),
                param( 'kernel.debug' ),
                service( 'request_stack' ),
                service( 'security.token_storage' ),
                service( 'security.csrf.token_manager' ),
            ],
        )
    ;

    $services
        ->set( UrlGeneratorExtension::class )
        ->args( [ service( 'router' ) ] )
    ;
};