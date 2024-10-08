<?php

declare( strict_types = 1 );

namespace Northrook\Symfony\Latte;

use Northrook\Latte;
use Northrook\Symfony\Latte\DependencyInjection\UrlGeneratorExtension;
use Northrook\Symfony\Latte\Runtime\App;
use Support\Normalize;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;


// Should put Latte on parity with Twig
// Cache through northrook/symfony-latte-cache


/**
 * @todo    Update URL to documentation : root of symfony-latte-bundle
 * @author  Martin Nielsen <mn@northrook.com>
 *
 * @link    https://github.com/northrook Documentation
 * @version 1.0 ☑️
 */
final class SymfonyLatteBundle extends AbstractBundle
{
    public function loadExtension(
        array                 $config,
        ContainerConfigurator $container,
        ContainerBuilder      $builder,
    ) : void
    {
        $container->import( '../config/runtime.php' );

        $builder->setParameter( 'dir.cache.latte', "%kernel.cache_dir%/latte" );

        $services = $container->services();

        $services
            ->defaults()
            ->autowire()
        ;

        $services
            ->set( Latte::class )
            ->args(
                [
                    Normalize::path( '%kernel.project_dir%' ),
                    Normalize::path( '%dir.cache.latte%' ),
                    param( 'kernel.default_locale' ),
                    service( 'debug.stopwatch' )->nullOnInvalid(),
                    service( 'logger' )->nullOnInvalid(),
                    param( 'kernel.debug' ),
                ],
            )
            ->call( 'addGlobalVariable', [ 'get', service( App::class ) ] )
            ->call( 'addExtension', [ service( UrlGeneratorExtension::class ) ] )
        ;
    }

    public function getPath() : string
    {
        return dirname( __DIR__ );
    }
}