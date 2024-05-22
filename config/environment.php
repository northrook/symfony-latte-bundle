<?php

//------------------------------------------------------------------
// config / Environment
//------------------------------------------------------------------

declare( strict_types = 1 );

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Northrook\Symfony\Latte\GlobalVariable;
use Northrook\Symfony\Latte\Loader;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Translation\LocaleSwitcher;

return static function ( ContainerConfigurator $container ) : void {


    /**
     * The parameter key used to access the  {@see GlobalVariable} in every template.
     *
     * - Overriding this parameter will change the key used to access the global variable.
     * - Setting this parameter to `null` will disable the global variable.
     */
    $container->parameters()->set( 'latte.global_variable.key', 'get' );

    $services = $container->services();

    /**
     * Latte Global Variable, injected into every template by the included {@see Loader}.
     *
     * - Variable key defaults to `get`, this can be changed by overriding the `latte.parameter_key.global` parameter.
     */
    $services->set( 'latte.global_variable', GlobalVariable::class )
             ->args(
                 [
                     param( 'kernel.environment' ),               // Environment<string>
                     param( 'kernel.debug' ),                     // Debug<bool>
                     service( 'latte.service.locator' ),
                 ],
             );


    /**
     * Latte {@see ServiceLocator}, used by the {@see GlobalVariable} for lazily accessing Symfony services.
     */
    $services->set( 'latte.service.locator', ServiceLocator::class )
             ->tag( 'container.service_locator' )
             ->args(
                 [
                     [
                         RequestStack::class              => service( 'request_stack' ),
                         RouterInterface::class           => service( 'router' ),
                         ParameterBagInterface::class     => service( 'parameter_bag' ),
                         TokenStorageInterface::class     => service( 'security.token_storage' ),
                         LocaleSwitcher::class            => service( 'translation.locale_switcher' )->nullOnInvalid(),
                         CsrfTokenManagerInterface::class => service( 'security.csrf.token_manager' ),
                         LoggerInterface::class           => service( 'logger' )->nullOnInvalid(),
                     ],
                 ],
             )
             ->public();

};