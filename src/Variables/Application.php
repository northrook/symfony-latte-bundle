<?php

namespace Northrook\Symfony\Latte\Variables;

use Northrook\Core\Cache;
use Northrook\Symfony\Latte\Variables\Application\ApplicationDependencies;
use Northrook\Symfony\Latte\Variables\Application\Env;
use Northrook\Symfony\Latte\Variables\Application\Theme;
use Northrook\Symfony\Latte\Variables\Application\UserAgent;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Global parameters, accessed via {@see $get}
 *
 * Equivalent to Twig's `app` global variable.
 *
 * * Access via `{$get}` by default
 * * Parameters are accessed by `$get->arrow->function()` syntax
 * * Full support for PHP autocompletion, see {@link https://example.com documentation} for details
 * * Exposes the same Symfony parameters and services as Twig's `app.variable`
 * * Intended to be extended with custom parameters and services
 *
 * @version 1.1 ✅
 *
 * @property Env               $env
 * @property UserAgent         $userAgent
 * @property Theme             $theme
 * @property bool              $debug
 * @property array             $enabledLocales
 *
 * @property string            $sitename
 * @property string            $language
 * @property ?Request          $request
 * @property string            $routeInfo
 * @property ?UserInterface    $user
 * @property ?SessionInterface $session
 */
final readonly class Application
{

    public function __construct(
        private string                  $environment,
        private bool                    $debug,
        private Cache                   $cache,
        private ApplicationDependencies $get,
    ) {}

    public function __isset( string $name ) : bool {
        return method_exists( $this, "get" . ucfirst( $name ) );
    }

    public function __get( string $name ) : mixed {
        return match ( strtolower( $name ) ) {
            'request'        => $this->getRequest(),
            'session'        => $this->getSession(),
            'env'            => $this->getEnv(),
            'theme'          => $this->getTheme(),
            'useragent'      => $this->getUserAgent(),
            'debug'          => $this->debug,
            'user'           => $this->get->tokenStorage->getToken()?->getUser(),
            'language'       => $this->locale(),
            'enabledLocales' => $this->getEnabledLocales(),
            'routeinfo'      => $this->getRequest()?->getPathInfo(),
            default          => null,
        };
    }

    /**
     * {@see Application} does not support dynamic properties.
     */
    public function __set( string $name, $value ) : void {}

    private function getEnv() : Env {
        return $this->cache->value(
            key   : 'env',
            value : new Env(
                        debug      : $this->debug,
                        authorized : (bool) $this->get->tokenStorage->getToken(),
                        production : $this->environment === 'prod',
                        staging    : $this->environment === 'staging',
                        dev        : $this->environment === 'dev',
                    ),
        );
    }

    private function getUserAgent() : UserAgent {
        return $this->cache->value( key : 'useragent', value : new UserAgent( $this->get->logger ) );
    }

    private function getTheme() : Theme {
        return $this->cache->value( key : 'theme', value : new Theme() );
    }

    private function getRequest() : ?Request {
        return $this->get->requestStack->getCurrentRequest();
    }

    private function getSession() : ?SessionInterface {
        return $this->getRequest()?->hasSession() ? $this->getRequest()?->getSession() : null;
    }

    public function locale( string $fallback = 'en' ) : string {
        return $this->cache->value( key : 'locale', value : $this->get->localeSwitcher?->getLocale() ?? $fallback );
    }

    private function getEnabledLocales() : array {
        return $this->get->getEnabledLocales() ?? [ $this->locale() => $this->locale() ];
    }

    /**
     * Returns some or all the existing flash messages:
     *  * getFlashes() returns all the flash messages
     *  * getFlashes('notice') returns a simple array with flash messages of that type
     *  * getFlashes(['notice', 'error']) returns a nested array of type => messages.
     */
    public function flashes( string | array | null $types = null ) : array {

        try {
            $session = $this->getSession();
        }
        catch ( RuntimeException ) {
            return [];
        }

        if ( !$session instanceof FlashBagAwareSessionInterface ) {
            return [];
        }

        if ( null === $types || '' === $types || [] === $types ) {
            return $session->getFlashBag()->all();
        }

        if ( is_string( $types ) ) {
            return $session->getFlashBag()->get( $types );
        }

        $return = [];

        foreach ( $types as $type ) {
            $return[ $type ] = $session->getFlashBag()->get( $type );
        }

        return $return;
    }
}