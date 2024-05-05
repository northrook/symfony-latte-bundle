<?php

namespace Northrook\Symfony\Latte\Variables\Application;

use Closure;
use Northrook\Core\Service\ServiceResolver;
use Northrook\Core\Service\ServiceResolverTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Translation\LocaleSwitcher;

/**
 * @property  RequestStack              $requestStack
 * @property  UrlGeneratorInterface     $urlGenerator
 * @property  TokenStorageInterface     $tokenStorage
 * @property  LocaleSwitcher            $localeSwitcher
 * @property  CsrfTokenManagerInterface $csrfTokenManager
 * @property  LoggerInterface           $logger
 */
final class ApplicationDependencies extends ServiceResolver
{
    use ServiceResolverTrait;

    private array $enabledLocales;

    public function __construct(
        RequestStack | Closure              $requestStack,
        UrlGeneratorInterface | Closure     $urlGenerator,
        TokenStorageInterface | Closure     $tokenStorage,
        LocaleSwitcher | Closure            $localeSwitcher,
        CsrfTokenManagerInterface | Closure $csrfTokenManager,
        LoggerInterface | Closure           $logger,
    ) {}

    public function getEnabledLocales() : ?array {
        return $this->enabledLocales ?? null;
    }


    public function setEnabledLocales( array $enabledLocales ) : void {
        $this->enabledLocales = $enabledLocales;
    }

}