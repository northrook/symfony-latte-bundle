<?php

namespace Northrook\Symfony\Latte\Runtime;

use Northrook\Core\Trait\PropertyAccessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @property-read bool            isDebug
 * @property-read bool            isProd
 * @property-read bool            isDev
 *
 * @property-read Request         $request
 * @property-read string          $pathInfo
 * @property-read string          $method
 *
 * @property-read string          $route
 * @property-read string          $routeName
 * @property-read string          $routeRoot
 *
 * @property-read ?UserInterface  $user
 * @property-read ?TokenInterface $token
 */
final readonly class App
{
    use PropertyAccessor;

    public function __construct(
        public string                     $environment,
        private bool                      $debug,
        private RequestStack              $requestStack,
        private TokenStorageInterface     $tokenStorage,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function __get( string $property ) {
        $get = match ( $property ) {
            'isDebug'   => $this->debug,
            'isProd'    => \str_starts_with( $this->environment, 'prod' ),
            'isDev'     => \str_starts_with( $this->environment, 'dev' ),

            'request'   => $this->currentRequest(),
            'pathInfo'  => $this->currentRequest()->getPathInfo(),
            'method'    => $this->currentRequest()->getMethod(),

            'route'     => $this->route(),
            'routeName' => $this->routeName(),
            'routeRoot' => $this->routeRoot(),

            'user'      => $this->tokenStorage->getToken()?->getUser(),
            'token'     => $this->tokenStorage->getToken(),
        };

        return $get;
    }

    public function csrfToken( string $tokenId ) : string {
        return $this->csrfTokenManager->getToken( $tokenId )->getValue();
    }


    private function currentRequest() : ?Request {
        return $this->requestStack->getCurrentRequest();
    }


    private function session() : ?SessionInterface {
        return $this->currentRequest()?->hasSession() ? $this->currentRequest()->getSession() : null;
    }


    /**
     * Resolve and cache the current route key
     *
     * @return string
     */
    private function route() : string {
        return $this->currentRequest()->attributes->get( 'route' ) ?? '';
    }

    /**
     * Resolve and cache the current route name
     *
     * @return string
     */
    private function routeName() : string {
        return $this->currentRequest()->get( '_route' ) ?? '';
    }

    /**
     * Resolve and cache the current route root name
     *
     * @return string
     */
    private function routeRoot() : string {
        return strstr( $this->routeName(), ':', true );
    }

}