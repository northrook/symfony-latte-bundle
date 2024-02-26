<?php

namespace Northrook\Symfony\Latte\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class SymfonyLatteExtension extends Extension
{

	/**
	 * @throws Exception
	 */
	public function load( array $configs, ContainerBuilder $container ) : void {
		$bundle = dirname( __DIR__, 2 );
		$config = new PhpFileLoader( $container, new FileLocator( $bundle . '/config' ) );
		$config->load( 'services.php' );
	}
}