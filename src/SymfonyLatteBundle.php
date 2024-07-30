<?php

declare( strict_types = 1 );

namespace Northrook\Symfony\Latte;

use Northrook\Latte;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

// Should put Latte on parity with Twig
// Cache through northrook/symfony-latte-cache

/**
 * @version 1.0 ☑️
 * @author  Martin Nielsen <mn@northrook.com>
 *
 * @link    https://github.com/northrook Documentation
 * @todo    Update URL to documentation : root of symfony-latte-bundle
 */
final class SymfonyLatteBundle extends AbstractBundle
{
    public function loadExtension(
        array                 $config,
        ContainerConfigurator $container,
        ContainerBuilder      $builder,
    ) : void {
        $services = $container->services();

        $services->defaults()
                 ->autowire();

        $services->set( Latte::class )
                 ->args( [ service( 'request_stack' ) ] );
    }

    public function getPath() : string {
        return dirname( __DIR__ );
    }
}